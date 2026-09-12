<?php

namespace App\Domain\Occasions\Actions;

use App\Domain\Occasions\Models\Holiday;
use App\Domain\Occasions\Models\Occasion;
use App\Domain\People\Enums\FieldSource;
use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * Materializează sărbătorile apropiate ca ocazii ale utilizatorului.
 *
 * O sărbătoare NU e legată de o persoană: 8 Martie nu e „a Anei”, e o dată cu
 * un public. De aceea ocazia are `person_id = null` și un `holiday_id`.
 *
 * Consecință de UX, importantă: o singură notificare pe sărbătoare, nu una
 * per persoană. „8 Martie e peste 3 zile, ai 12 persoane pe listă” — nu
 * douăsprezece notificări separate.
 *
 * Se materializează per an, fiindcă Paștele se mută.
 */
class SyncHolidayOccasions
{
    /** Cât de departe materializăm. Trebuie să acopere cel mai mare prag de reminder. */
    private const HORIZON_DAYS = 45;

    public function __invoke(User $user, ?CarbonImmutable $now = null): int
    {
        $now = ($now ?? CarbonImmutable::now())->setTimezone($user->timezone);

        $holidays = Holiday::query()
            ->where('country_code', $user->country_code)
            ->where('is_active', true)
            ->get();

        $created = 0;

        foreach ($holidays as $holiday) {
            $date = $holiday->nextDate($now);

            if ($now->diffInDays($date) > self::HORIZON_DAYS) {
                continue;
            }

            $occasion = Occasion::firstOrNew([
                'user_id'    => $user->id,
                'holiday_id' => $holiday->id,
                'year'       => $date->year,
            ]);

            if ($occasion->exists) {
                continue;
            }

            $occasion->fill([
                'person_id' => null,
                'type'      => 'holiday',
                'month'     => $date->month,
                'day'       => $date->day,
                'source'    => FieldSource::Derived,
                // O sărbătoare e un fapt de calendar, nu o presupunere.
                'confidence'   => 1.0,
                'confirmed_at' => now(),
            ])->save();

            $created++;
        }

        return $created;
    }
}
