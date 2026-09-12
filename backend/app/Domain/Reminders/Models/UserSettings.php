<?php

namespace App\Domain\Reminders\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSettings extends Model
{
    protected $table = 'user_settings';

    protected $guarded = [];

    /**
     * Valorile implicite stau AICI, nu doar în migrare.
     *
     * `firstOrCreate` întoarce instanța construită în memorie, fără valorile
     * implicite ale bazei de date. Fără acestea, `push_enabled` era `null`
     * pentru un utilizator nou — deci fals — și nu se planifica nimic.
     */
    protected $attributes = [
        'preferred_hour' => 10,
        'quiet_from'     => 22,
        'quiet_to'       => 8,
        'push_enabled'   => true,
        'email_digest'   => true,
    ];

    protected $casts = [
        'reminder_days'  => 'array',
        'push_enabled'   => 'boolean',
        'email_digest'   => 'boolean',
        'preferred_hour' => 'integer',
        'quiet_from'     => 'integer',
        'quiet_to'       => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /** @return list<int> */
    public function reminderDays(): array
    {
        $days = $this->reminder_days ?: config('wishio.reminders.default_days_before');

        // Descrescător: 7, 3, 1 — ordinea în care se trimit.
        rsort($days);

        return $days;
    }

    /** Ora cade în intervalul de liniște? Intervalul trece peste miezul nopții. */
    public function isQuietHour(int $hour): bool
    {
        return $this->quiet_from > $this->quiet_to
            ? $hour >= $this->quiet_from || $hour < $this->quiet_to
            : $hour >= $this->quiet_from && $hour < $this->quiet_to;
    }
}
