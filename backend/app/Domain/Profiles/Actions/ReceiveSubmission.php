<?php

namespace App\Domain\Profiles\Actions;

use App\Domain\People\Models\Person;
use App\Domain\Profiles\Models\ProfileSubmission;
use App\Support\Names\NameNormalizer;

/**
 * Ce se întâmplă cu o completare nouă, trimisă din pagina publică.
 *
 *  1. Același om a mai completat linkul, cu același nume complet → datele merg
 *     la persoana lui: cea apărută din link sau contactul confirmat deja de
 *     proprietar.
 *  2. Numele seamănă cu contacte existente → completarea așteaptă alegerea
 *     proprietarului și nu atinge niciun contact până atunci (S9.8).
 *  3. Altfel → persoană nouă, imediat.
 *
 * Doar cazul 2 așteaptă. Celelalte ajung imediat: o completare care stă într-o
 * coadă de aprobare se pierde.
 */
class ReceiveSubmission
{
    public function __construct(
        private readonly AcceptSubmission $accept,
        private readonly FindIdentityCandidates $candidates,
        private readonly NameNormalizer $normalizer,
    ) {}

    /** @return Person|null persoana care a primit datele, sau null dacă se așteaptă proprietarul */
    public function __invoke(ProfileSubmission $submission): ?Person
    {
        if ($person = $this->knownPerson($submission)) {
            return ($this->accept)($submission, $person);
        }

        if (($this->candidates)($submission)->isNotEmpty()) {
            return null;
        }

        return ($this->accept)($submission);
    }

    /** Persoana unui om care a mai completat același link, cu același nume complet. */
    private function knownPerson(ProfileSubmission $submission): ?Person
    {
        $name = $this->normalizer->normalize($submission->display_name);

        // Un nume fără nicio literă nu identifică pe nimeni.
        if ($name === '') {
            return null;
        }

        return ProfileSubmission::query()
            ->where('public_profile_id', $submission->public_profile_id)
            ->whereKeyNot($submission->id)
            ->whereNotNull('accepted_at')
            // Un contact existent doar dacă proprietarul a confirmat legătura.
            ->where(fn ($query) => $query->whereNotNull('identity_confirmed_at')
                ->orWhereHas('person', fn ($person) => $person->where('from_public_link', true)))
            ->whereHas('person', fn ($person) => $person->whereNull('archived_at'))
            ->with('person')
            ->latest('id')
            ->get()
            ->first(fn (ProfileSubmission $earlier) => $this->normalizer->normalize($earlier->display_name) === $name)
            ?->person;
    }
}
