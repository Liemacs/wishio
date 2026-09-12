<?php

namespace App\Domain\Reminders\Jobs;

use App\Domain\Occasions\Actions\SyncHolidayOccasions;
use App\Domain\Reminders\Actions\ScheduleReminders;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Rulează zilnic. Vezi docs/05 § 7. */
class ScheduleRemindersForAllUsers implements ShouldQueue
{
    use Queueable;

    public function handle(ScheduleReminders $schedule, SyncHolidayOccasions $syncHolidays): void
    {
        User::query()->chunkById(200, function ($users) use ($schedule, $syncHolidays) {
            foreach ($users as $user) {
                // Întâi materializăm sărbătorile apropiate, apoi planificăm:
                // altfel primul reminder de 8 Martie ar apărea abia a doua zi.
                $syncHolidays($user);
                $schedule($user);
            }
        });
    }
}
