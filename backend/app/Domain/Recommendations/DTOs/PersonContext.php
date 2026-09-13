<?php

namespace App\Domain\Recommendations\DTOs;

use App\Domain\People\Models\Person;

/**
 * Ce trimitem spre AI despre o persoană.
 *
 * PSEUDONIMIZAT, deliberat: fără nume, fără telefon, fără email și fără niciun
 * text scris de utilizator. Notele, „de evitat” scris liber și cadourile
 * introduse de mână pot conține date sensibile („e diabetic”, „alergic la
 * nuci”) și nu părăsesc serverul. Pleacă doar coduri din taxonomie și titluri
 * din catalog. Vezi docs/05 § 5, docs/06 § 2 și docs/21.
 */
readonly class PersonContext
{
    /** @param list<string> $interests @param list<string> $avoid @param list<string> $alreadyReceived */
    public function __construct(
        public ?string $relationship,
        public ?string $gender,
        public ?string $ageBracket,
        public array $interests,
        public array $avoid,
        public array $alreadyReceived,
        public ?int $budgetMin,
        public ?int $budgetMax,
        public string $occasionType = 'birthday',
    ) {}

    public static function fromPerson(Person $person, ?int $budgetMin, ?int $budgetMax, string $occasionType = 'birthday'): self
    {
        return new self(
            relationship: $person->relationship,
            gender: $person->gender,
            ageBracket: self::bracket($person->age()),
            interests: $person->interests->pluck('code')->all(),
            avoid: $person->avoids->map(fn ($a) => $a->interest?->code)->filter()->values()->all(),
            // Titlul unui cadou introdus de mână e textul utilizatorului
            // („tabloul cu noi doi la mare”), nu un produs: pleacă doar cele din catalog.
            alreadyReceived: $person->giftHistory->map(fn ($entry) => $entry->product?->title)->filter()->values()->all(),
            budgetMin: $budgetMin ?? $person->budget_min,
            budgetMax: $budgetMax ?? $person->budget_max,
            occasionType: $occasionType,
        );
    }

    private static function bracket(?int $age): ?string
    {
        return match (true) {
            $age === null => null,
            $age < 18     => 'under-18',
            $age < 25     => '18-24',
            $age < 35     => '25-34',
            $age < 50     => '35-49',
            default       => '50+',
        };
    }

    public function toArray(): array
    {
        return [
            'relationship'     => $this->relationship,
            'gender'           => $this->gender,
            'age_bracket'      => $this->ageBracket,
            'interests'        => $this->interests,
            'avoid'            => $this->avoid,
            'already_received' => $this->alreadyReceived,
            'budget'           => ['min' => $this->budgetMin, 'max' => $this->budgetMax],
            'occasion'         => $this->occasionType,
        ];
    }
}
