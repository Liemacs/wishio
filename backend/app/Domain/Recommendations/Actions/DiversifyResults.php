<?php

namespace App\Domain\Recommendations\Actions;

use Illuminate\Support\Collection;

/**
 * Evită listele monotone.
 *
 * Fără asta, cel mai bun interes al persoanei ar umple toate cele opt locuri:
 * opt perechi de căști pentru cineva care ascultă muzică. Lista arată sărac
 * chiar dacă fiecare produs în parte e potrivit.
 */
class DiversifyResults
{
    /**
     * @param  Collection<int, array{product: mixed, score: float, breakdown: array}> $scored
     * @return Collection<int, array{product: mixed, score: float, breakdown: array}>
     */
    public function __invoke(Collection $scored, int $limit): Collection
    {
        $maxPerCategory = (int) config('wishio.recommendations.max_per_category');
        $maxPerMerchant = (int) config('wishio.recommendations.max_per_merchant');

        $perCategory = [];
        $perMerchant = [];
        $selected    = collect();

        foreach ($scored as $candidate) {
            if ($selected->count() >= $limit) {
                break;
            }

            $product  = $candidate['product'];
            $category = $product->product_category_id ?? 0;
            $merchant = $product->bestOffer()?->merchant_id ?? 0;

            if (($perCategory[$category] ?? 0) >= $maxPerCategory) {
                continue;
            }

            if (($perMerchant[$merchant] ?? 0) >= $maxPerMerchant) {
                continue;
            }

            $perCategory[$category] = ($perCategory[$category] ?? 0) + 1;
            $perMerchant[$merchant] = ($perMerchant[$merchant] ?? 0) + 1;

            $selected->push($candidate);
        }

        return $selected->values();
    }
}
