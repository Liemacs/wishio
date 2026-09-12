<?php

namespace App\Domain\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutboundClick extends Model
{
    protected $guarded = [];

    protected $casts = ['price' => 'decimal:2'];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }
}
