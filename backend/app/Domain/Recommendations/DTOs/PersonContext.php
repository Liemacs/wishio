<?php

namespace App\Domain\Recommendations\DTOs;

use App\Domain\People\Models\Person;

/**
 * Ce trimitem spre AI despre o persoană.
 *
 * PSEUDONIMIZAT, deliberat: fără nume, fără telefon, fără email, fără notele
 * libere ale utilizatorului. Notele pot conține date sensibile („e diabetic”,
 * „divorțează”) și nu părăsesc serverul. Vezi docs/05 § 5 și docs/06 § 2.
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
            avoid: $person->avoids->map(fn ($a) => $a->interest?->code ?? $a->free_text)->filter()->values()->all(),
            alreadyReceived: $person->giftHistory->pluck('title')->all(),
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
