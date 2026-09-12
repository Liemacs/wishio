<?php

namespace App\Domain\People\Actions;

use App\Domain\Occasions\Actions\SyncOccasionsForPerson;
use App\Domain\People\DTOs\ImportSummary;
use App\Domain\People\Enums\FieldSource;
use App\Domain\People\Models\Person;
use App\Models\User;
use App\Support\Names\NameNormalizer;
use Illuminate\Support\Facades\DB;

/**
 * Importă contactele pe care utilizatorul le-a ales explicit.
 *
 * Ce NU primește și nu stochează: numere de telefon, fotografii, emailuri,
 * adrese sau restul agendei. Doar numele și, dacă există, ziua de naștere —
 * vezi docs/00 § D-017 și cerința App Store 5.1.2 din docs/06 § 2.
 *
 * Datele intră ca `device_contact`, deci o modificare manuală ulterioară le
 * protejează definitiv, iar o re-sincronizare nu strică nimic scris de om.
 */
class ImportContacts
{
    public function __construct(
        private readonly WritePersonField $writeField,
        private readonly SyncOccasionsForPerson $syncOccasions,
        private readonly NameNormalizer $normalizer,
    ) {}

    /**
     * @param list<array{device_contact_id: string, display_name: string,
     *                   birth_date?: ?string, birth_year_known?: bool}> $contacts
     */
    public function __invoke(User $user, array $contacts): ImportSummary
    {
        $summary = new ImportSummary();

        DB::transaction(function () use ($user, $contacts, $summary) {
            foreach ($contacts as $contact) {
                $person = $this->upsertPerson($user, $contact, $summary);
                $this->countOccasions($person, $summary);
                $summary->personIds[] = $person->id;
            }
        });

        return $summary;
    }

    private function upsertPerson(User $user, array $contact, ImportSummary $summary): Person
    {
        $person = $user->people()
            ->withTrashed()
            ->where('device_contact_id', $contact['device_contact_id'])
            ->first();

        if ($person === null) {
            $person = $user->people()->create([
                'device_contact_id'     => $contact['device_contact_id'],
                'display_name'          => $contact['display_name'],
                'given_name_normalized' => $this->normalizer->firstName($contact['display_name']),
            ]);
            $summary->created++;
        } else {
            // Un contact șters din aplicație și reimportat revine la viață,
            // cu tot cu note și interese.
            $person->restore();
            $summary->updated++;
        }

        // Numele trece prin ierarhia de încredere: dacă utilizatorul l-a
        // schimbat manual, agenda nu are voie să revină peste.
        ($this->writeField)($person, 'display_name', $contact['display_name'], FieldSource::DeviceContact);

        if (! empty($contact['birth_date'])) {
            ($this->writeField)($person, 'birth_date', $contact['birth_date'], FieldSource::DeviceContact);
            ($this->writeField)(
                $person,
                'birth_year_known',
                (bool) ($contact['birth_year_known'] ?? false),
                FieldSource::DeviceContact
            );
        }

        $person->refresh();

        if ($person->wasChanged('display_name') || $person->given_name_normalized === null) {
            $person->update([
                'given_name_normalized' => $this->normalizer->firstName($person->display_name),
            ]);
        }

        return $person;
    }

    private function countOccasions(Person $person, ImportSummary $summary): void
    {
        foreach (($this->syncOccasions)($person) as $occasion) {
            if ($occasion->type === 'birthday') {
                $summary->birthdays++;

                continue;
            }

            if ($occasion->type === 'name_day') {
                $summary->nameDays++;

                if ($occasion->confirmed_at === null && $occasion->rejected_at === null) {
                    $summary->nameDaysToConfirm++;
                }
            }
        }
    }
}
