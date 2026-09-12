<?php

namespace App\Domain\Occasions\DTOs;

use App\Domain\Occasions\Models\NameDay;

readonly class NameDayMatch
{
    public function __construct(
        public NameDay $nameDay,
        public string $matchedName,
        public float $confidence,
        public bool $isDiminutive,
    ) {}

    /**
     * Peste acest prag propunem onomastica utilizatorului spre confirmare.
     * Push-ul cere in plus is_verified = true — vezi docs/15-onomastici.md.
     */
    public function isConfident(): bool
    {
        return $this->confidence >= config('wishio.reminders.min_confidence_to_push');
    }
}
