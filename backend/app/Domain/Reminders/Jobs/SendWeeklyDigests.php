<?php

namespace App\Domain\Reminders\Jobs;

use App\Domain\Reminders\Actions\BuildWeeklyDigest;
use App\Domain\Reminders\Mail\WeeklyDigest;
use App\Domain\Reminders\Models\UserSettings;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

/**
 * Rezumatul săptămânal, luni dimineața în fusul fiecărui utilizator.
 *
 * Jobul rulează din oră în oră și alege pe cine e momentul să anunțe — altfel
 * un utilizator din alt fus ar primi emailul la miezul nopții.
 *
 * Vezi docs/05 § 7 și docs/03 (plasă de siguranță pentru cine a refuzat push).
 */
class SendWeeklyDigests implements ShouldQueue
{
    use Queueable;

    private const SEND_WEEKDAY = CarbonImmutable::MONDAY;

    private const SEND_HOUR = 9;

    public function handle(BuildWeeklyDigest $buildDigest, ?CarbonImmutable $now = null): void
    {
        $now = $now ?? CarbonImmutable::now();

        User::query()
            ->whereHas('settings', fn ($q) => $q->where('email_digest', true))
            ->with('settings')
            ->chunkById(200, function ($users) use ($buildDigest, $now) {
                foreach ($users as $user) {
                    $this->maybeSend($user, $buildDigest, $now);
                }
            });
    }

    private function maybeSend(User $user, BuildWeeklyDigest $buildDigest, CarbonImmutable $now): void
    {
        $local = $now->setTimezone($user->timezone);

        if ($local->dayOfWeek !== self::SEND_WEEKDAY || $local->hour !== self::SEND_HOUR) {
            return;
        }

        $settings = $user->settings;

        // Jobul rulează din oră în oră; fără verificarea asta, o repornire
        // în aceeași oră ar trimite emailul de două ori.
        if ($settings?->last_digest_sent_at?->greaterThan($now->subDays(6))) {
            return;
        }

        $entries = $buildDigest($user);

        // Un email săptămânal gol transformă produsul în spam mai repede
        // decât orice. Dacă nu e nimic de spus, nu trimitem.
        if ($entries->isEmpty()) {
            return;
        }

        Mail::to($user->email)->send(new WeeklyDigest($user, $entries));

        UserSettings::updateOrCreate(['user_id' => $user->id], ['last_digest_sent_at' => $now]);
    }
}
