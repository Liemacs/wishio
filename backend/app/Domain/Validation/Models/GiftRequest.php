<?php

namespace App\Domain\Validation\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Cerere din Faza 0. Temporar — se sterge dupa validare (docs/14 § 6).
 */
class GiftRequest extends Model
{
    protected $guarded = [];

    protected $casts = [
        'occasion_date'   => 'date',
        'consented_at'    => 'datetime',
        'answered_at'     => 'datetime',
        'would_buy'       => 'boolean',
        'clicked_through' => 'boolean',
    ];

    public function isAnswered(): bool
    {
        return $this->answered_at !== null;
    }
}
