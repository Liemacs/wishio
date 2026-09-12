<?php

namespace App\Domain\Reminders\Jobs;

use App\Domain\Reminders\Actions\ScheduleReminders;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Rulează zilnic. Vezi docs/05 § 7. */
class ScheduleRemindersForAllUsers implements ShouldQueue
{
    use Queueable;

    public function handle(ScheduleReminders $schedule): void
    {
        User::query()->chunkById(200, function ($users) use ($schedule) {
            foreach ($users as $user) {
                $schedule($user);
            }
        });
    }
}
