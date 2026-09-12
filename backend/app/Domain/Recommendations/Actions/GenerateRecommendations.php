<?php

namespace App\Domain\Recommendations\Actions;

use App\Domain\Catalog\Models\Product;
use App\Domain\Occasions\Models\Occasion;
use App\Domain\People\Models\Person;
use App\Domain\Recommendations\DTOs\GiftCriteria;
use App\Domain\Recommendations\DTOs\PersonContext;
use App\Domain\Recommendations\Models\RecommendationItem;
use App\Domain\Recommendations\Models\RecommendationRun;
use App\Support\Ai\AiProvider;
use App\Support\Ai\RuleBasedAiProvider;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Pipeline-ul complet de recomandare. Vezi docs/05 § 3.
 *
 *   criterii → filtre deterministe → scoring → diversificare → explicații
 *
 * Regula care îl guvernează: AI-ul nu produce niciodată un produs. Produce
 * criterii și explicații; produsele vin exclusiv din catalogul nostru, iar
 * orice item care nu există în setul selectat se aruncă la validare.
 */
class GenerateRecommendations
{
    public function __construct(
        private readonly AiProvider $ai,
        private readonly ScoreCandidates $score,
        private readonly DiversifyResults $diversify,
    ) {}

    public function __invoke(
        Person $person,
        ?int $budgetMin = null,
        ?int $budgetMax = null,
        ?Occasion $occasion = null,
    ): RecommendationRun {
        $startedAt = microtime(true);

        $person->loadMissing(['interests', 'avoids.interest', 'giftHistory', 'user']);

        $context = PersonContext::fromPerson($person, $budgetMin, $budgetMax, $occasion?->type ?? 'birthday');

        // Fără consimțământ AI folosim ruta pe reguli. Nu e o degradare tăcută:
        // interfața arată produsele fără explicații, ceea ce e onest.
        $provider = $person->user->ai_consent_at !== null ? $this->ai : new RuleBasedAiProvider();

        $run = RecommendationRun::create([
            'user_id'      => $person->user_id,
            'person_id'    => $person->id,
            'occasion_id'  => $occasion?->id,
            'budget_min'   => $context->budgetMin,
            'budget_max'   => $context->budgetMax,
            'kind'         => 'gift',
            'locale'       => $person->user->locale,
            'provider'     => $provider->name(),
            'status'       => 'pending',
        ]);

        try {
            $criteria = $provider->generateGiftCriteria($context);
            $run->update(['criteria' => $criteria->toArray()]);

            $candidates = $this->candidates($person, $criteria);
            $scored     = ($this->score)($candidates, $criteria);
            $selected   = ($this->diversify)($scored, (int) config('wishio.recommendations.result_size'));

            $reasons = $this->explanations($provider, $selected, $context, $run->locale);

            $this->persist($run, $selected, $reasons);

            $run->update([
                'status'      => 'ready',
                'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
            ]);
        } catch (\Throwable $exception) {
            $run->update(['status' => 'failed', 'failure' => mb_substr($exception->getMessage(), 0, 190)]);
        }

        return $run->fresh(['items.product.offers.merchant']);
    }

    /**
     * Filtrele deterministe. Tot ce se poate exclude în SQL se exclude aici,
     * nu în model sau în AI.
     *
     * @return Collection<int, Product>
     */
    private function candidates(Person $person, GiftCriteria $criteria): Collection
    {
        /*
         * Fără niciun interes nu avem pe ce construi o recomandare.
         *
         * Întoarcem gol, deliberat. Varianta alternativă — primele opt produse
         * bine cotate din catalog — ar arăta ca un rezultat, dar ar fi opt
         * produse la întâmplare. „Nu avem destule idei bune” e un răspuns mai
         * bun decât trei sugestii proaste (docs/02 § R7).
         */
        if ($criteria->isEmpty()) {
            return collect();
        }

        $avoidCodes = $person->avoids->pluck('interest.code')->filter()->all();
        $avoidCodes = array_values(array_unique([...$avoidCodes, ...$criteria->avoidInterests]));

        // Ce a primit deja: „Alex a primit căști anul trecut, hai altceva”.
        $alreadyReceived = $person->giftHistory->pluck('product_id')->filter()->all();

        return Product::query()
            ->recommendable()
            ->inBudget($criteria->priceMin, $criteria->priceMax)
            ->when($criteria->interests !== [], fn ($query) => $query
                ->whereHas('interests', fn ($q) => $q->whereIn('code', $criteria->interests)))
            ->when($avoidCodes !== [], fn ($query) => $query
                ->whereDoesntHave('interests', fn ($q) => $q->whereIn('code', $avoidCodes)))
            ->when($alreadyReceived !== [], fn ($query) => $query->whereNotIn('id', $alreadyReceived))
            ->with(['offers.merchant', 'interests', 'category'])
            ->limit(200)
            ->get();
    }

    /** @return array<int, string> */
    private function explanations(AiProvider $provider, Collection $selected, PersonContext $context, string $locale): array
    {
        if ($selected->isEmpty()) {
            return [];
        }

        $titles = $selected->map(fn (array $item) => $item['product']->title)->all();

        try {
            $reasons = $provider->explain($titles, $context, $locale);
        } catch (\Throwable) {
            // Explicațiile sunt un plus, nu o condiție. Dacă furnizorul cade,
            // utilizatorul primește tot produsele.
            return [];
        }

        // Validare: acceptăm doar explicații pentru produsele pe care LE-AM ales.
        return array_intersect_key($reasons, $titles);
    }

    private function persist(RecommendationRun $run, Collection $selected, array $reasons): void
    {
        DB::transaction(function () use ($run, $selected, $reasons) {
            foreach ($selected as $rank => $item) {
                RecommendationItem::create([
                    'recommendation_run_id' => $run->id,
                    'product_id'            => $item['product']->id,
                    'rank'                  => $rank + 1,
                    'score'                 => $item['score'],
                    'score_breakdown'       => $item['breakdown'],
                    'reason'                => $reasons[$rank] ?? null,
                ]);
            }
        });
    }
}
