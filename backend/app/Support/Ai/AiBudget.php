<?php

namespace App\Support\Ai;

use Illuminate\Support\Facades\Cache;

/**
 * Limitator de cheltuială pentru apelurile AI.
 *
 * Rolul lui nu e economia, ci limitarea daunei: o buclă greșită, un atac sau
 * un val neașteptat de trafic nu trebuie să poată consuma bugetul pe o lună
 * într-o noapte. Când plafonul e atins, produsul trece pe ruta fără AI și
 * continuă să funcționeze — degradare, nu cădere (docs/11 § 3).
 */
class AiBudget
{
    public function spentToday(): float
    {
        return (float) Cache::get($this->key(), 0.0);
    }

    public function dailyCap(): float
    {
        return (float) config('wishio.ai.max_cost_per_day');
    }

    public function hasRoom(): bool
    {
        return $this->dailyCap() <= 0 || $this->spentToday() < $this->dailyCap();
    }

    public function record(float $cost): void
    {
        // Expiră singur la finalul zilei: nu avem nevoie de istoric aici,
        // costul real se vede în recommendation_runs.
        Cache::put($this->key(), $this->spentToday() + $cost, now()->endOfDay());
    }

    private function key(): string
    {
        return 'wishio.ai.spend.'.now()->toDateString();
    }
}
