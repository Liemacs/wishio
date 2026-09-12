<?php

namespace App\Domain\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Merchant extends Model
{
    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean'];

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }
}
