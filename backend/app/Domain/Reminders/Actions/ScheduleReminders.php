<?php

namespace App\Domain\Reminders\Actions;

use App\Domain\Occasions\Models\Occasion;
use App\Domain\Reminders\Models\QueuedNotification;
use App\Domain\Reminders\Models\UserSettings;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Planifică notificările pentru ocaziile apropiate ale unui utilizator.
 *
 * Se rulează zilnic, dar planifică pe un orizont de câteva săptămâni: dacă o
 * rulare e ratată, următoarea recuperează, fiindcă nu depinde de „azi e exact
 * ziua N”. Constrângerea de unicitate împiedică dublurile.
 *
 * Vezi docs/05 § 7 (anti-spam) și docs/09 § 3 (scara de mesaje).
 */
class ScheduleReminders
{
    /** Cât de departe planificăm. Trebuie să depășească cel mai mare prag. */
    private const HORIZON_DAYS = 35;

    public function __invoke(User $user, ?CarbonImmutable $now = null): int
    {
        $now      = $now ?? CarbonImmutable::now();
        $settings = $this->settingsFor($user);

        if (! $settings->push_enabled) {
            return 0;
        }

        $maxPerOccasion = (int) config('wishio.reminders.max_per_occasion');
        $maxPerDay      = (int) config('wishio.reminders.max_push_per_day');

        // Cele mai urgente primele: dacă ziua e plină, reminderul de mâine are
        // prioritate față de cel de peste o săptămână.
        $thresholds = collect($settings->reminderDays())->push(0)->unique()->sort()->take($maxPerOccasion);

        $occasions = Occasion::query()
            ->where('user_id', $user->id)
            ->with(['person', 'nameDay'])
            ->get()
            ->filter(fn (Occasion $occasion) => $occasion->mayNotify());

        $scheduled = 0;

        DB::transaction(function () use ($occasions, $thresholds, $settings, $user, $now, $maxPerDay, &$scheduled) {
            foreach ($occasions as $occasion) {
                $occurrence = $this->nextOccurrence($occasion, $now, $user->timezone);

                if ($occurrence === null) {
                    continue;
                }

                foreach ($thresholds as $daysBefore) {
                    $when = $this->notificationMoment($occurrence, (int) $daysBefore, $settings);

                    // Momentul a trecut deja — nu trimitem remindere expirate.
                    if ($when->lessThanOrEqualTo($now)) {
                        continue;
                    }

                    // Atenție la ordine: diffInDays e semnat în Carbon 3.
                    // `$when->diffInDays($now)` pe o dată viitoare dă negativ,
                    // iar verificarea orizontului n-ar prinde niciodată.
                    if ($now->diffInDays($when) > self::HORIZON_DAYS) {
                        continue;
                    }

                    if ($this->dayIsFull($user, $when, $maxPerDay)) {
                        continue;
                    }

                    QueuedNotification::firstOrCreate(
                        [
                            'occasion_id'   => $occasion->id,
                            'channel'       => 'push',
                            'days_before'   => (int) $daysBefore,
                            'occasion_year' => $occurrence->year,
                        ],
                        [
                            'user_id' => $user->id,
                            // Limba se fixează acum, nu la trimitere.
                            'locale'        => $user->locale,
                            'scheduled_for' => $when->utc(),
                        ]
                    );

                    $scheduled++;
                }
            }
        });

        return $scheduled;
    }

    private function settingsFor(User $user): UserSettings
    {
        return UserSettings::firstOrCreate(['user_id' => $user->id]);
    }

    /** Următoarea apariție a ocaziei, în fusul utilizatorului. */
    private function nextOccurrence(Occasion $occasion, CarbonImmutable $now, string $timezone): ?CarbonImmutable
    {
        $local = $now->setTimezone($timezone);

        try {
            $occurrence = CarbonImmutable::create($local->year, $occasion->month, $occasion->day, 0, 0, 0, $timezone);
        } catch (\Throwable) {
            return null;   // 31 februarie și alte date imposibile
        }

        return $occurrence->endOfDay()->lessThan($local)
            ? $occurrence->addYear()
            : $occurrence;
    }

    /**
     * Momentul exact al notificării: ora preferată, mutată în afara orelor
     * de liniște. O notificare la 3 dimineața nu e un reminder.
     */
    private function notificationMoment(CarbonImmutable $occurrence, int $daysBefore, UserSettings $settings): CarbonImmutable
    {
        $hour = $settings->preferred_hour;

        if ($settings->isQuietHour($hour)) {
            $hour = $settings->quiet_to;
        }

        return $occurrence->subDays($daysBefore)->setTime($hour, 0);
    }

    /** Limita zilnică se aplică pe ziua locală a utilizatorului. */
    private function dayIsFull(User $user, CarbonImmutable $when, int $maxPerDay): bool
    {
        $localDay = $when->setTimezone($user->timezone);

        $count = QueuedNotification::where('user_id', $user->id)
            ->where('channel', 'push')
            ->whereBetween('scheduled_for', [
                $localDay->startOfDay()->utc(),
                $localDay->endOfDay()->utc(),
            ])
            ->count();

        return $count >= $maxPerDay;
    }
}
