<?php

namespace App\Domain\Profiles\Jobs;

use App\Domain\Profiles\Models\ProfileSubmission;
use App\Domain\Profiles\Models\PublicProfile;
use App\Domain\Reminders\Models\DeviceToken;
use App\Domain\Reminders\Models\QueuedNotification;
use App\Domain\Reminders\Models\UserSettings;
use App\Support\Push\PushMessage;
use App\Support\Push\PushSender;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;

/**
 * Îi spune proprietarului că o completare așteaptă „cine este?” (S9.8).
 *
 * Până răspunde, datele nu ajung la nicio persoană, deci nici reminderele.
 * Fără notificare, proprietarul afla doar când deschidea aplicația.
 *
 * Rulează des, dar nu trimite imediat:
 *  - lasă câteva minute după completare, ca prietenii care completează după
 *    același mesaj de grup să producă o singură notificare;
 *  - nimic în orele de liniște: se reîncearcă la rulările următoare;
 *  - cel mult o notificare pe zi, iar limita zilnică e comună cu reminderele;
 *  - textul se construiește la trimitere, din ce așteaptă în acel moment.
 */
class NotifyOwnersOfPendingSubmissions implements ShouldQueue
{
    use Queueable;

    /** Cât așteptăm după o completare, ca cele venite în rafală să se adune. */
    public const GRACE_MINUTES = 10;

    public function handle(PushSender $sender): void
    {
        PublicProfile::query()
            ->whereHas('submissions', fn ($query) => $query->pending()
                ->whereNull('owner_notified_at')
                ->where('created_at', '<=', now()->subMinutes(self::GRACE_MINUTES)))
            ->with('user')
            ->get()
            ->each(fn (PublicProfile $profile) => $this->notify($profile, $sender));
    }

    private function notify(PublicProfile $profile, PushSender $sender): void
    {
        $user = $profile->user;
        $settings = UserSettings::firstOrCreate(['user_id' => $user->id]);
        $local = now()->setTimezone($user->timezone);

        if (! $settings->push_enabled || $settings->isQuietHour($local->hour)) {
            return;
        }

        if ($this->notifiedInLastDay($profile) || $this->dayIsFull($user->id, $local)) {
            return;
        }

        $devices = DeviceToken::where('user_id', $user->id)->get();

        // Fără telefon înregistrat nu marcăm nimic: notificarea pleacă după înregistrare.
        if ($devices->isEmpty()) {
            return;
        }

        $pending = $profile->submissions()->pending()->oldest('id')->get();
        $content = $this->content($pending, $user->locale);

        $failures = $sender->send($devices->map(fn (DeviceToken $device) => new PushMessage(
            token: $device->token,
            title: $content['title'],
            body: $content['body'],
            data: $content['data'],
        ))->all());

        // Marcăm chiar dacă furnizorul a eșuat: altfel aceeași notificare s-ar
        // repeta la fiecare rulare.
        ProfileSubmission::whereKey($pending->modelKeys())
            ->whereNull('owner_notified_at')
            ->update(['owner_notified_at' => now()]);

        foreach ($failures as $token => $reason) {
            // Token invalid: aplicația a fost dezinstalată sau telefonul resetat.
            if (in_array($reason, ['DeviceNotRegistered', 'InvalidCredentials'], true)) {
                DeviceToken::where('token', $token)->delete();
            }
        }
    }

    private function notifiedInLastDay(PublicProfile $profile): bool
    {
        return $profile->submissions()->where('owner_notified_at', '>', now()->subDay())->exists();
    }

    /**
     * Limita zilnică de push pe ziua locală, împărțită cu reminderele planificate
     * pentru azi. O notificare despre completări trimisă deja azi e oprită mai
     * sus, de limita de una pe zi.
     */
    private function dayIsFull(int $userId, CarbonInterface $local): bool
    {
        $reminders = QueuedNotification::where('user_id', $userId)
            ->where('channel', 'push')
            ->whereNull('failure')
            ->whereBetween('scheduled_for', [
                $local->copy()->startOfDay()->utc(),
                $local->copy()->endOfDay()->utc(),
            ])
            ->count();

        return $reminders >= (int) config('wishio.reminders.max_push_per_day');
    }

    /**
     * @param  Collection<int, ProfileSubmission>  $pending
     * @return array{title: string, body: string, data: array<string, mixed>}
     */
    private function content(Collection $pending, string $locale): array
    {
        // Numele persoanei în primele cuvinte, un singur verb de acțiune (docs/09 § 3).
        if ($pending->count() === 1) {
            $submission = $pending->first();

            return [
                'title' => __('wishio.submissions.one_title', ['name' => $submission->display_name], $locale),
                'body'  => __('wishio.submissions.one_body', [], $locale),
                'data'  => ['type' => 'submission', 'submission_id' => $submission->id],
            ];
        }

        return [
            'title' => trans_choice('wishio.submissions.many_title', $pending->count(), ['count' => $pending->count()], $locale),
            'body'  => __('wishio.submissions.many_body', [], $locale),
            // Mai multe completări: ecranul principal le arată pe toate.
            'data' => ['type' => 'submissions'],
        ];
    }
}
