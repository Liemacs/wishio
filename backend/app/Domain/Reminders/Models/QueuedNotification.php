<?php

namespace App\Domain\Reminders\Models;

use App\Domain\Occasions\Models\Occasion;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QueuedNotification extends Model
{
    use MassPrunable;

    protected $table = 'notifications_queue';

    protected $guarded = [];

    protected $casts = [
        'scheduled_for' => 'datetime',
        'sent_at'       => 'datetime',
        'opened_at'     => 'datetime',
        'days_before'   => 'integer',
        'occasion_year' => 'integer',
    ];

    public function occasion(): BelongsTo
    {
        return $this->belongsTo(Occasion::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Istoricul reminderelor se păstrează 13 luni (docs/21, M-11). Planificarea
     * nu reface niciodată un reminder al cărui moment a trecut
     * (`ScheduleReminders`), deci ștergerea nu poate duce la o retrimitere.
     */
    public function prunable(): Builder
    {
        return static::query()->where(
            'scheduled_for',
            '<',
            now()->subMonths((int) config('wishio.retention.notification_queue_months')),
        );
    }
}
