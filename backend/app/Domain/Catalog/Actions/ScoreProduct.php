<?php

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Models\Product;
use App\Support\Names\NameNormalizer;

/**
 * Cât de „de cadou” e un produs, 1–5.
 *
 * Primul dintre cele trei straturi din docs/05 § 4: reguli pe toate produsele,
 * apoi corecție manuală pe cele mai văzute, apoi învățare din comportament.
 *
 * Contează pentru că un catalog de 70.000 de produse conține în majoritate
 * cabluri, filtre și consumabile. Dacă intră toate în recomandări, produsul
 * pare prost chiar dacă motorul e corect.
 */
class ScoreProduct
{
    /** Nu pot fi cadou niciodată, indiferent de preț sau categorie. */
    private const NEVER_A_GIFT = [
        'cablu', 'adaptor', 'incarcator', 'filtru', 'cartus', 'toner',
        'consumabil', 'piesa', 'garnitura', 'surub', 'baterie', 'acumulator',
        'siguranta', 'ulei de motor', 'antigel', 'detergent', 'sac de aspirator',
        'кабель', 'адаптер', 'фильтр', 'картридж', 'запчаст', 'батарейк',
        'моторное масло', 'антифриз', 'мешок для пылесоса',
    ];

    /** Sub acest preț rareori e un cadou serios. */
    private const CHEAP_THRESHOLD = 150;

    /** Peste acest preț, alegerea nu mai e a noastră. */
    private const EXPENSIVE_THRESHOLD = 20000;

    public function __construct(private readonly NameNormalizer $normalizer) {}

    /** @return array{gift_score: int, is_giftable: bool} */
    public function __invoke(Product $product, ?float $price = null): array
    {
        // Scorurile puse de om nu se ating: stratul 2 bate stratul 1.
        if ($product->score_is_manual) {
            return ['gift_score' => $product->gift_score, 'is_giftable' => $product->is_giftable];
        }

        $haystack = $this->normalizer->normalize($product->title.' '.($product->description ?? ''));

        foreach (self::NEVER_A_GIFT as $word) {
            if (str_contains($haystack, $this->normalizer->normalize($word))) {
                return ['gift_score' => 1, 'is_giftable' => false];
            }
        }

        $score = $product->category?->gift_base_score ?? 3;

        // Un brand cunoscut face produsul mai ușor de oferit: primitorul
        // înțelege ce a primit.
        if (filled($product->brand)) {
            $score++;
        }

        if ($price !== null && $price < self::CHEAP_THRESHOLD) {
            $score--;
        }

        if ($price !== null && $price > self::EXPENSIVE_THRESHOLD) {
            $score--;
        }

        $score = max(1, min(5, $score));

        return ['gift_score' => $score, 'is_giftable' => $score >= 2];
    }
}
