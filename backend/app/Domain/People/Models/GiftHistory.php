<?php

namespace App\Domain\People\Models;

use App\Domain\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GiftHistory extends Model
{
    protected $table = 'gift_history';

    protected $guarded = [];

    protected $casts = ['amount' => 'decimal:2', 'year' => 'integer'];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
