<?php

namespace App\Domain\Reminders\Jobs;

use App\Domain\Reminders\Actions\BuildReminderContent;
use App\Domain\Reminders\Models\DeviceToken;
use App\Domain\Reminders\Models\QueuedNotification;
use App\Support\Push\PushMessage;
use App\Support\Push\PushSender;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Trimite notificările ajunse la scadență. Rulează des (la câteva minute).
 *
 * Marcăm `sent_at` chiar dacă trimiterea eșuează: altfel o problemă la
 * furnizor ar produce retrimiteri la fiecare rulare, iar utilizatorul ar
 * primi același reminder de zece ori. Eșecul se păstrează în `failure`.
 */
class SendDueNotifications implements ShouldQueue
{
    use Queueable;

    public function handle(PushSender $sender, BuildReminderContent $buildContent): void
    {
        $due = QueuedNotification::query()
            ->whereNull('sent_at')
            ->where('channel', 'push')
            ->where('scheduled_for', '<=', now())
            // Un reminder vechi de peste o zi nu mai are sens: ocazia a trecut.
            ->where('scheduled_for', '>', now()->subDay())
            ->with(['occasion.person', 'occasion.nameDay'])
            ->limit(500)
            ->get();

        if ($due->isEmpty()) {
            return;
        }

        $tokensByUser = DeviceToken::whereIn('user_id', $due->pluck('user_id')->unique())
            ->get()
            ->groupBy('user_id');

        $messages = [];
        $tokenToNotification = [];

        foreach ($due as $notification) {
            // Ocazia poate fi între timp ștearsă, oprită sau respinsă.
            if (! $notification->occasion?->mayNotify()) {
                $notification->update(['sent_at' => now(), 'failure' => 'occasion_no_longer_notifiable']);

                continue;
            }

            $content = $buildContent($notification->occasion, $notification->days_before, $notification->locale);

            foreach ($tokensByUser->get($notification->user_id, collect()) as $device) {
                $messages[] = new PushMessage(
                    token: $device->token,
                    title: $content['title'],
                    body: $content['body'],
                    data: [
                        'occasion_id' => $notification->occasion_id,
                        'person_id'   => $notification->occasion->person_id,
                        'type'        => $notification->occasion->type,
                    ],
                );

                $tokenToNotification[$device->token][] = $notification->id;
            }

            $notification->update(['sent_at' => now()]);
        }

        if ($messages === []) {
            return;
        }

        foreach ($sender->send($messages) as $token => $reason) {
            QueuedNotification::whereIn('id', $tokenToNotification[$token] ?? [])
                ->update(['failure' => $reason]);

            // Token invalid — dispozitivul a fost dezinstalat sau resetat.
            if (in_array($reason, ['DeviceNotRegistered', 'InvalidCredentials'], true)) {
                DeviceToken::where('token', $token)->delete();
            }
        }
    }
}
