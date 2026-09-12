<?php

namespace App\Console\Commands;

use App\Domain\People\Models\Interest;
use App\Domain\People\Models\Person;
use App\Domain\People\Models\PersonAvoid;
use App\Domain\Recommendations\Actions\GenerateRecommendations;
use App\Domain\Recommendations\Models\RecommendationRun;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Rulează setul de evaluare al motorului de recomandări (C8, docs/05 § 3).
 *
 *   php artisan wishio:eval            — raport complet
 *   php artisan wishio:eval --ci       — ieșire scurtă, cod de eroare la regresie
 *
 * Rulează în CI la fiecare schimbare de ponderi, prompturi sau filtre de
 * catalog. Fără el, „am îmbunătățit recomandările” e o presupunere.
 */
class EvaluateRecommendationsCommand extends Command
{
    protected $signature = 'wishio:eval {--ci : Ieșire scurtă, cod de eroare la eșec}';

    protected $description = 'Evaluează motorul de recomandări pe setul de profiluri';

    /** Sub acest procent de profiluri trecute, considerăm că e o regresie. */
    private const PASS_THRESHOLD = 0.90;

    public function handle(GenerateRecommendations $generate): int
    {
        $profiles = require database_path('seeders/data/eval_profiles.php');
        $results = [];

        // Rulăm pe date temporare: evaluarea nu trebuie să lase urme.
        DB::beginTransaction();

        $user = User::factory()->create(['locale' => 'ro']);

        foreach ($profiles as $profile) {
            $results[] = $this->evaluate($profile, $user, $generate);
        }

        DB::rollBack();

        return $this->report($results);
    }

    private function evaluate(array $profile, User $user, GenerateRecommendations $generate): array
    {
        $person = Person::create(['user_id' => $user->id, 'display_name' => 'Eval']);

        if ($profile['interests'] !== []) {
            $ids = Interest::whereIn('code', $profile['interests'])->pluck('id');
            $person->interests()->attach(
                $ids->mapWithKeys(fn ($id) => [$id => ['source' => 'owner_manual', 'confidence' => 0.9]])->all()
            );
        }

        foreach ($profile['avoid'] ?? [] as $code) {
            PersonAvoid::create([
                'person_id'   => $person->id,
                'interest_id' => Interest::where('code', $code)->value('id'),
            ]);
        }

        $startedAt = microtime(true);
        $run = $generate($person->fresh(['interests', 'avoids.interest', 'giftHistory']),
            $profile['budget_min'], $profile['budget_max']);
        $latency = (int) ((microtime(true) - $startedAt) * 1000);

        return [
            'name'     => $profile['name'],
            'count'    => $run->items->count(),
            'latency'  => $latency,
            'failures' => $this->check($profile, $run),
        ];
    }

    /** @return list<string> */
    private function check(array $profile, RecommendationRun $run): array
    {
        $failures = [];
        $items = $run->items;

        if ($run->status !== 'ready') {
            return ["rulare eșuată: {$run->failure}"];
        }

        if ($items->count() < ($profile['min_results'] ?? 1)) {
            $failures[] = sprintf('prea puține rezultate (%d < %d)', $items->count(), $profile['min_results'] ?? 1);
        }

        if (isset($profile['max_results']) && $items->count() > $profile['max_results']) {
            $failures[] = sprintf('prea multe rezultate (%d > %d)', $items->count(), $profile['max_results']);
        }

        $codes = $items->flatMap(fn ($item) => $item->product->interests->pluck('code'))->unique();

        if (($profile['expect_any'] ?? []) !== [] && $codes->intersect($profile['expect_any'])->isEmpty()) {
            $failures[] = 'niciun rezultat din interesele așteptate';
        }

        foreach ($profile['forbid_any'] ?? [] as $forbidden) {
            if ($codes->contains($forbidden)) {
                $failures[] = "a apărut un interes exclus: $forbidden";
            }
        }

        if ($profile['in_budget'] ?? true) {
            foreach ($items as $item) {
                $price = (float) $item->product->bestOffer()?->price;

                if ($profile['budget_min'] !== null && $price < $profile['budget_min']) {
                    $failures[] = sprintf('sub buget: %s la %.0f', $item->product->title, $price);
                }

                if ($profile['budget_max'] !== null && $price > $profile['budget_max']) {
                    $failures[] = sprintf('peste buget: %s la %.0f', $item->product->title, $price);
                }
            }
        }

        return $failures;
    }

    private function report(array $results): int
    {
        $passed = collect($results)->filter(fn ($r) => $r['failures'] === []);
        $failed = collect($results)->filter(fn ($r) => $r['failures'] !== []);
        $rate = count($results) > 0 ? $passed->count() / count($results) : 0;
        $latencies = collect($results)->pluck('latency')->sort()->values();
        $p95 = $latencies->get((int) floor($latencies->count() * 0.95)) ?? $latencies->last();

        if (! $this->option('ci')) {
            foreach ($results as $result) {
                $this->line(sprintf(
                    ' %s %-46s %2d rezultate  %3d ms',
                    $result['failures'] === [] ? '<fg=green>✓</>' : '<fg=red>✗</>',
                    mb_substr($result['name'], 0, 44),
                    $result['count'],
                    $result['latency'],
                ));

                foreach ($result['failures'] as $failure) {
                    $this->line("   <fg=red>→</> $failure");
                }
            }

            $this->newLine();
        }

        $this->line(sprintf(
            '%s  %d/%d profiluri trecute (%.0f%%)  ·  latență p95 %d ms',
            $rate >= self::PASS_THRESHOLD ? '<fg=green;options=bold>OK</>' : '<fg=red;options=bold>REGRESIE</>',
            $passed->count(), count($results), $rate * 100, $p95,
        ));

        if ($rate < self::PASS_THRESHOLD) {
            $this->newLine();
            $this->error(sprintf('Sub pragul de %.0f%%. Ce s-a stricat:', self::PASS_THRESHOLD * 100));

            foreach ($failed as $result) {
                $this->line("  · {$result['name']}: ".implode('; ', $result['failures']));
            }

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
