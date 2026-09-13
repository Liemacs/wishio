<?php

namespace App\Domain\Catalog\Jobs;

use App\Domain\Catalog\Models\OutboundClick;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

/**
 * Clickurile mai vechi de 24 de luni devin un total pe lună, comerciant și
 * ofertă, iar rândurile individuale se șterg (docs/21, M-08). Politica promite
 * exact asta: „24 de luni, apoi doar agregat”.
 *
 * Rulează în fiecare noapte, deci prinde doar clickurile care au trecut pragul
 * de la rularea precedentă. O lună se adună astfel în mai multe nopți: totalul
 * crește, nu se rescrie.
 */
class AggregateOldClicks implements ShouldQueue
{
    use Queueable;

    /** @return int câte clickuri au intrat în agregat */
    public function handle(): int
    {
        $cutoff = now()->subMonths((int) config('wishio.retention.clicks_months'));
        $aggregated = 0;

        DB::transaction(function () use ($cutoff, &$aggregated) {
            $groups = OutboundClick::query()->toBase()
                ->where('created_at', '<', $cutoff)
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m-01') AS month, merchant_id, offer_id, COUNT(*) AS clicks")
                ->groupBy('month', 'merchant_id', 'offer_id')
                ->get();

            $now = now();

            foreach ($groups as $group) {
                $key = ['month' => $group->month, 'merchant_id' => $group->merchant_id, 'offer_id' => $group->offer_id];

                DB::table('click_stats')->insertOrIgnore($key + ['total' => 0, 'created_at' => $now, 'updated_at' => $now]);
                DB::table('click_stats')->where($key)->increment('total', (int) $group->clicks, ['updated_at' => $now]);

                $aggregated += (int) $group->clicks;
            }

            OutboundClick::query()->where('created_at', '<', $cutoff)->delete();
        });

        return $aggregated;
    }
}
