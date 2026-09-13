<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Laravel\Sanctum\PersonalAccessToken as SanctumToken;

/**
 * Tokenurile de acces nu expiră (`sanctum.expiration = null`): pe telefon,
 * omul rămâne conectat. Dacă aplicația nu s-a mai deschis de 12 luni, tokenul
 * se șterge (docs/21, M-11), iar la revenire omul se autentifică din nou.
 * Pushurile folosesc `device_tokens`, nu tokenul de acces, deci reminderele
 * ajung în continuare.
 */
class PersonalAccessToken extends SanctumToken
{
    use MassPrunable;

    public function prunable(): Builder
    {
        $cutoff = now()->subMonths((int) config('wishio.retention.inactive_token_months'));

        return static::query()->where(fn (Builder $query) => $query
            ->where('last_used_at', '<', $cutoff)
            ->orWhere(fn (Builder $never) => $never->whereNull('last_used_at')->where('created_at', '<', $cutoff)));
    }
}
