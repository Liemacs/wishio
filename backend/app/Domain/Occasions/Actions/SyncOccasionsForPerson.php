<?php

namespace App\Domain\Occasions\Actions;

use App\Domain\Occasions\Models\Occasion;
use App\Domain\People\Enums\FieldSource;
use App\Domain\People\Models\Person;
use Illuminate\Support\Collection;

/**
 * Reconstruiește ocaziile derivate ale unei persoane: ziua de naștere și
 * onomastica dedusă din prenume.
 *
 * Onomastica este cel mai important mecanism de cold-start al produsului:
 * din 200 de contacte poate 8 au ziua completată, dar ~150 au prenume.
 * Vezi docs/02 § R1 și docs/15-onomastici.md.
 */
class SyncOccasionsForPerson
{
    public function __construct(private readonly ResolveNameDay $resolveNameDay) {}

    /** @return Collection<int, Occasion> ocaziile persoanei, după sincronizare */
    public function __invoke(Person $person): Collection
    {
        $this->syncBirthday($person);
        $this->syncNameDay($person);

        return $person->occasions()->with('nameDay')->get();
    }

    private function syncBirthday(Person $person): void
    {
        if ($person->birth_date === null) {
            // Ziua a fost ștearsă: scoatem ocazia, dar numai dacă nu a
            // confirmat-o cineva manual între timp.
            $person->occasions()
                ->where('type', 'birthday')
                ->whereNull('confirmed_at')
                ->delete();

            return;
        }

        $source = $person->trustLevel('birth_date') ?? FieldSource::OwnerManual;

        Occasion::updateOrCreate(
            [
                'person_id' => $person->id,
                'type'      => 'birthday',
                'month'     => (int) $person->birth_date->month,
                'day'       => (int) $person->birth_date->day,
            ],
            [
                'user_id'    => $person->user_id,
                'year'       => $person->birth_year_known ? (int) $person->birth_date->year : null,
                'source'     => $source,
                'confidence' => $source->defaultConfidence(),
                // O zi de naștere venită din agendă sau scrisă de utilizator
                // nu are nevoie de confirmare: nu e o presupunere.
                'confirmed_at' => now(),
            ]
        );

        // Dacă data s-a schimbat, ocaziile vechi neconfirmate nu mai au sens.
        $person->occasions()
            ->where('type', 'birthday')
            ->where(fn ($q) => $q->where('month', '!=', $person->birth_date->month)
                ->orWhere('day', '!=', $person->birth_date->day))
            ->delete();
    }

    private function syncNameDay(Person $person): void
    {
        if ($person->display_name === '') {
            return;
        }

        $match = ($this->resolveNameDay)->best(
            $person->display_name,
            $person->user->country_code ?? 'MD',
            $person->user->name_day_calendar ?? 'orthodox_new',
        );

        if ($match === null) {
            $person->occasions()
                ->where('type', 'name_day')
                ->whereNull('confirmed_at')
                ->whereNull('rejected_at')
                ->delete();

            return;
        }

        $existing = $person->occasions()->where('type', 'name_day')->first();

        // Utilizatorul a spus deja ceva despre onomastica acestei persoane —
        // a confirmat-o sau a respins-o. Nu ne întoarcem peste decizia lui.
        if ($existing && ($existing->confirmed_at !== null || $existing->rejected_at !== null)) {
            return;
        }

        $existing?->delete();

        Occasion::create([
            'user_id'     => $person->user_id,
            'person_id'   => $person->id,
            'type'        => 'name_day',
            'month'       => $match->nameDay->month,
            'day'         => $match->nameDay->day,
            'name_day_id' => $match->nameDay->id,
            'source'      => FieldSource::Derived,
            'confidence'  => $match->confidence,
        ]);
    }
}
