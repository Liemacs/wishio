<?php

namespace App\Domain\Occasions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NameDayAlias extends Model
{
    protected $guarded = [];

    protected $casts = [
        'confidence'    => 'float',
        'is_diminutive' => 'boolean',
    ];

    public function nameDay(): BelongsTo
    {
        return $this->belongsTo(NameDay::class);
    }
}
