<?php

namespace App\Domain\Reminders\Models;

use App\Domain\Occasions\Models\Occasion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QueuedNotification extends Model
{
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
        return $this->belongsTo(\App\Models\User::class);
    }
}
