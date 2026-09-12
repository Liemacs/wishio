<?php

namespace App\Domain\People\DTOs;

/** Ce a găsit importul — alimentează direct ecranul O8 (docs/09). */
class ImportSummary
{
    public int $created = 0;

    public int $updated = 0;

    public int $birthdays = 0;

    public int $nameDays = 0;

    public int $nameDaysToConfirm = 0;

    /** @var list<int> */
    public array $personIds = [];

    public function toArray(): array
    {
        return [
            'created'              => $this->created,
            'updated'              => $this->updated,
            'birthdays'            => $this->birthdays,
            'name_days'            => $this->nameDays,
            'name_days_to_confirm' => $this->nameDaysToConfirm,
            'person_ids'           => $this->personIds,
        ];
    }
}
