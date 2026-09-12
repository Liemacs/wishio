<?php

namespace App\Domain\Reminders\DTOs;

use App\Domain\Occasions\Models\Occasion;

readonly class DigestEntry
{
    public function __construct(
        public string $title,
        public string $subtitle,
        public int $daysUntil,
        public string $type,
        public ?int $personId,
    ) {}

    public static function fromOccasion(Occasion $occasion, string $locale): self
    {
        if ($occasion->isHoliday()) {
            $people = $occasion->audience()->count();

            return new self(
                title: $occasion->holiday->label($locale),
                subtitle: trim(trans_choice('wishio.holiday.people', $people, ['count' => $people], $locale)),
                daysUntil: $occasion->daysUntil(),
                type: 'holiday',
                personId: null,
            );
        }

        return new self(
            title: $occasion->person->display_name,
            subtitle: __("wishio.occasions.{$occasion->type}", [], $locale),
            daysUntil: $occasion->daysUntil(),
            type: $occasion->type,
            personId: $occasion->person_id,
        );
    }
}
