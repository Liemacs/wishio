<?php

namespace App\Domain\Recommendations\Actions;

use App\Domain\Catalog\Models\Product;
use App\Domain\Recommendations\DTOs\GiftCriteria;
use Illuminate\Support\Collection;

/**
 * Ordonarea candidaților. DETERMINISTĂ — fără AI.
 *
 * Motivul e explicat în docs/05 § 3: cu un catalog de câteva sute de produse
 * taguite, un scor pe ponderi dă rezultate mai bune ȘI explicabile față de
 * similaritate vectorială. În plus, costul nu crește cu mărimea catalogului,
 * iar un rezultat prost se poate depana — se vede exact ce componentă l-a urcat.
 *
 * Ponderile stau în config/wishio.php și însumează 1.0.
 */
class ScoreCandidates
{
    /**
     * @param  Collection<int, Product> $candidates
     * @return Collection<int, array{product: Product, score: float, breakdown: array<string, float>}>
     */
    public function __invoke(Collection $candidates, GiftCriteria $criteria): Collection
    {
        $weights = config('wishio.recommendations.weights');
        $wanted  = $criteria->interests;

        return $candidates
            ->map(function (Product $product) use ($criteria, $weights, $wanted) {
                $breakdown = [
                    'interest_overlap' => $this->interestOverlap($product, $wanted),
                    'budget_fit'       => $this->budgetFit($product, $criteria),
                    'gift_score'       => ($product->gift_score - 1) / 4,
                    'freshness'        => $this->freshness($product),
                    'sponsored'        => $this->sponsored($product),
                ];

                $score = 0.0;

                foreach ($breakdown as $component => $value) {
                    $score += $value * ($weights[$component] ?? 0);
                }

                return [
                    'product'   => $product,
                    'score'     => round($score, 4),
                    'breakdown' => array_map(fn (float $v) => round($v, 3), $breakdown),
                ];
            })
            ->sortByDesc('score')
            ->values();
    }

    /** @param list<string> $wanted */
    private function interestOverlap(Product $product, array $wanted): float
    {
        if ($wanted === []) {
            return 0.0;
        }

        $matched = $product->interests->whereIn('code', $wanted);

        if ($matched->isEmpty()) {
            return 0.0;
        }

        $sum = $matched->sum(fn ($interest) => (float) $interest->pivot->weight);

        // Normalizăm la cel mult trei potriviri: un produs care atinge trei
        // interese e deja foarte relevant, iar al patrulea nu-l face mai bun.
        return min(1.0, $sum / min(3, count($wanted)));
    }

    private function budgetFit(Product $product, GiftCriteria $criteria): float
    {
        $offer = $product->bestOffer();

        if ($offer === null) {
            return 0.0;
        }

        // Fără buget declarat, prețul nu spune nimic despre potrivire.
        if ($criteria->priceMin === null && $criteria->priceMax === null) {
            return 0.5;
        }

        $min = $criteria->priceMin ?? 0;
        $max = $criteria->priceMax ?? ($min * 3 ?: (float) $offer->price);

        if ($max <= $min) {
            return 1.0;
        }

        $middle = ($min + $max) / 2;
        $half   = ($max - $min) / 2;

        // Cel mai bun scor la mijlocul intervalului: cine spune „500–1500”
        // se gândește de obicei la ~1000, nu la extreme.
        return max(0.0, 1 - abs((float) $offer->price - $middle) / $half);
    }

    private function freshness(Product $product): float
    {
        $offer = $product->bestOffer();

        return match (true) {
            $offer?->last_seen_at === null              => 0.4,
            $offer->last_seen_at->gt(now()->subDays(3)) => 1.0,
            $offer->last_seen_at->gt(now()->subDays(7)) => 0.7,
            default                                     => 0.4,
        };
    }

    private function sponsored(Product $product): float
    {
        return $product->offers->where('in_stock', true)->contains('is_sponsored', true) ? 1.0 : 0.0;
    }
}
