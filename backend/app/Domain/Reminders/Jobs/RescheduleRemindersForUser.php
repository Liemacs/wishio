<?php

namespace App\Domain\Reminders\Jobs;

use App\Domain\Reminders\Actions\ScheduleReminders;
use App\Domain\Reminders\Models\QueuedNotification;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Replanifică notificările unui utilizator după ce și-a schimbat preferințele.
 *
 * Planificatorul nu modifică rândurile existente. Fără acest job, ora,
 * treptele și intervalul de liniște se aplicau doar ocaziilor planificate de
 * acum înainte, iar cine oprea notificările le primea în continuare, până la
 * orizontul de 35 de zile.
 *
 * Ștergem doar ce e neexpediat și în viitor. Ce s-a trimis rămâne: altfel
 * constrângerea de unicitate n-ar mai împiedica retrimiterea aceleiași trepte.
 * Jobul e idempotent, deci două rulări apropiate nu produc dubluri.
 */
class RescheduleRemindersForUser implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $userId) {}

    public function handle(ScheduleReminders $schedule): void
    {
        $user = User::find($this->userId);

        // Contul poate fi șters între timp.
        if ($user === null) {
            return;
        }

        QueuedNotification::where('user_id', $user->id)
            ->where('channel', 'push')
            ->whereNull('sent_at')
            ->where('scheduled_for', '>', now())
            ->delete();

        $schedule($user);
    }
}
