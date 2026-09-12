<?php

namespace App\Domain\Recommendations\DTOs;

use App\Domain\People\Models\Interest;

/**
 * Contractul dintre AI și catalog.
 *
 * AI-ul poate produce DOAR asta. Nu produce produse, nu produse nume de
 * magazine, nu prețuri. Vezi docs/05 § 3 și regula 2 din CLAUDE.md.
 */
readonly class GiftCriteria
{
    /**
     * @param list<string> $interests        coduri din taxonomie
     * @param list<string> $avoidInterests   coduri din taxonomie
     */
    public function __construct(
        public array $interests = [],
        public array $avoidInterests = [],
        public ?int $priceMin = null,
        public ?int $priceMax = null,
        public float $confidence = 0.5,
    ) {}

    /**
     * Construiește din răspunsul brut al AI-ului, aruncând tot ce nu e valid.
     *
     * Codurile inexistente NU se repară și nu se aproximează — se aruncă.
     * Un cod inventat care „seamănă” cu unul real ar produce recomandări
     * plauzibile și greșite, cel mai prost rezultat posibil.
     */
    public static function fromAi(array $raw): self
    {
        $valid = Interest::validCodes();

        return new self(
            interests: array_values(array_intersect($raw['interests'] ?? [], $valid)),
            avoidInterests: array_values(array_intersect($raw['avoid_interests'] ?? [], $valid)),
            priceMin: isset($raw['price_range']['min']) ? (int) $raw['price_range']['min'] : null,
            priceMax: isset($raw['price_range']['max']) ? (int) $raw['price_range']['max'] : null,
            confidence: min(1.0, max(0.0, (float) ($raw['confidence'] ?? 0.5))),
        );
    }

    public function toArray(): array
    {
        return [
            'interests'       => $this->interests,
            'avoid_interests' => $this->avoidInterests,
            'price_range'     => ['min' => $this->priceMin, 'max' => $this->priceMax],
            'confidence'      => $this->confidence,
        ];
    }

    public function isEmpty(): bool
    {
        return $this->interests === [];
    }
}
