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

        // Dacă aceeași persoană completează de două ori, actualizăm, nu dublăm.
        $person = $submission->person
            ?? $user->people()
                ->where('given_name_normalized', $this->normalizer->firstName($submission->display_name))
                ->whereNull('archived_at')
                ->first()
            ?? $user->people()->create([
                'display_name'          => $submission->display_name,
                'given_name_normalized' => $this->normalizer->firstName($submission->display_name),
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
}
