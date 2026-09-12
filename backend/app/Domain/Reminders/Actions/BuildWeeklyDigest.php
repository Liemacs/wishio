<?php

namespace App\Domain\Reminders\Actions;

use App\Domain\Occasions\Models\Occasion;
use App\Domain\Reminders\DTOs\DigestEntry;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Ocaziile de pus într-un digest săptămânal.
 *
 * Rolul lui e dublu: plasă de siguranță pentru cine a refuzat notificările
 * push (docs/02 § R3) și motiv de a deschide aplicația când nu e nimic
 * iminent (docs/03 § v1.1).
 *
 * Dacă nu e nimic de spus, nu trimitem nimic. Un email săptămânal gol
 * transformă produsul în spam mai repede decât orice.
 */
class BuildWeeklyDigest
{
    /** Cât în față ne uităm. Destul cât să fie util, nu atât cât să fie zgomot. */
    private const HORIZON_DAYS = 30;

    /** @return Collection<int, DigestEntry> */
    public function __invoke(User $user): Collection
    {
        return Occasion::query()
            ->where('user_id', $user->id)
            ->whereNull('rejected_at')
            ->where('is_muted', false)
            ->with(['person', 'nameDay', 'holiday'])
            ->get()
            ->filter(fn (Occasion $occasion) => $occasion->mayNotify())
            ->filter(fn (Occasion $occasion) => $occasion->daysUntil() <= self::HORIZON_DAYS)
            ->sortBy(fn (Occasion $occasion) => $occasion->daysUntil())
            ->map(fn (Occasion $occasion) => DigestEntry::fromOccasion($occasion, $user->locale))
            ->values();
    }
}
