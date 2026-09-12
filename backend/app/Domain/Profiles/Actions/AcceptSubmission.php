<?php

namespace App\Domain\Profiles\Actions;

use App\Domain\Occasions\Actions\SyncOccasionsForPerson;
use App\Domain\People\Actions\WritePersonField;
use App\Domain\People\Enums\FieldSource;
use App\Domain\People\Models\Interest;
use App\Domain\People\Models\Person;
use App\Domain\Profiles\Models\ProfileSubmission;
use App\Support\Names\NameNormalizer;

/**
 * Transformă o completare de pe pagina publică într-o persoană reală.
 *
 * Datele intră ca `subject_provided` — al doilea nivel de încredere, sub doar
 * confirmarea directă a persoanei în aplicație. Sunt mai bune decât orice
 * import din agendă, fiindcă vin de la sursă, cu consimțământ explicit
 * (docs/04 § 3).
 *
 * O completare nu atinge niciodată un contact care exista deja. Potrivirea
 * după prenume lipea „Ana Popescu” de „Ana Rusu” din agendă și îi suprascria
 * numele și ziua (docs/00 § D-021). Legarea de un contact existent va cere
 * confirmarea proprietarului (PLAN.md S9.8).
 */
class AcceptSubmission
{
    public function __construct(
        private readonly WritePersonField $writeField,
        private readonly SyncOccasionsForPerson $syncOccasions,
        private readonly NameNormalizer $normalizer,
    ) {}

    public function __invoke(ProfileSubmission $submission): Person
    {
        $user = $submission->profile->user;

        $person = $this->earlierPersonOfSameSubmitter($submission)
            ?? $user->people()->create([
                'display_name'          => $submission->display_name,
                'given_name_normalized' => $this->normalizer->firstName($submission->display_name),
                'from_public_link'      => true,
            ]);

        ($this->writeField)($person, 'display_name', $submission->display_name, FieldSource::SubjectProvided);

        if ($submission->birth_date) {
            ($this->writeField)($person, 'birth_date', $submission->birth_date, FieldSource::SubjectProvided);
            ($this->writeField)($person, 'birth_year_known', $submission->birth_year_known, FieldSource::SubjectProvided);
        }

        if ($submission->interest_codes) {
            $ids = Interest::whereIn('code', $submission->interest_codes)->pluck('id');

            $person->interests()->syncWithoutDetaching(
                $ids->mapWithKeys(fn (int $id) => [$id => [
                    'source'     => FieldSource::SubjectProvided->value,
                    'confidence' => FieldSource::SubjectProvided->defaultConfidence(),
                ]])->all()
            );
        }

        ($this->syncOccasions)($person->fresh());

        $submission->update(['person_id' => $person->id, 'accepted_at' => now()]);

        return $person->fresh();
    }

    /**
     * Persoana creată de o completare anterioară a aceluiași om — același link,
     * același nume complet —, ca o a doua completare să actualizeze, nu să dubleze.
     *
     * Doar persoane apărute din link. Un contact din agendă sau adăugat de
     * proprietar nu e niciodată candidat, oricât ar semăna numele.
     */
    private function earlierPersonOfSameSubmitter(ProfileSubmission $submission): ?Person
    {
        $name = $this->normalizer->normalize($submission->display_name);

        // Un nume fără nicio literă nu identifică pe nimeni.
        if ($name === '') {
            return null;
        }

        return ProfileSubmission::query()
            ->where('public_profile_id', $submission->public_profile_id)
            ->whereKeyNot($submission->id)
            ->whereHas('person', fn ($query) => $query->where('from_public_link', true)->whereNull('archived_at'))
            ->with('person')
            ->latest('id')
            ->get()
            ->first(fn (ProfileSubmission $earlier) => $this->normalizer->normalize($earlier->display_name) === $name)
            ?->person;
    }
}
