<?php

namespace App\Domain\Profiles\Actions;

use App\Domain\People\Models\Person;
use App\Domain\Profiles\Models\ProfileSubmission;
use App\Support\Names\NameNormalizer;
use Illuminate\Support\Collection;

/**
 * Contactele proprietarului care ar putea fi omul care a completat.
 *
 * Doar contactele PROPRIETARULUI, niciodată ale altcuiva (docs/00 § D-021 și
 * regula 3). Un nume seamănă dacă prenumele din completare apare oriunde în
 * numele contactului: agendele păstrează des „Rusu Ana”.
 *
 * Ordinea ajută alegerea: întâi numele complet identic, apoi același prenume.
 */
class FindIdentityCandidates
{
    public function __construct(private readonly NameNormalizer $normalizer) {}

    /** @return Collection<int, Person> */
    public function __invoke(ProfileSubmission $submission): Collection
    {
        $firstName = $this->normalizer->firstName($submission->display_name);

        if ($firstName === null) {
            return collect();
        }

        $fullName = $this->normalizer->normalize($submission->display_name);

        return $submission->profile->user->people()
            ->whereNull('archived_at')
            ->get()
            ->filter(fn (Person $person) => in_array($firstName, $this->normalizer->candidates($person->display_name), true))
            ->sortBy(fn (Person $person) => sprintf(
                '%d%d%s',
                $this->normalizer->normalize($person->display_name) === $fullName ? 0 : 1,
                $person->given_name_normalized === $firstName ? 0 : 1,
                $this->normalizer->normalize($person->display_name),
            ))
            ->values();
    }
}
