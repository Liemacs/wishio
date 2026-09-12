<?php

namespace App\Domain\People\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InterestGroup extends Model
{
    protected $guarded = [];

    protected $casts = ['translations' => 'array'];

    public function interests(): HasMany
    {
        return $this->hasMany(Interest::class)->orderBy('sort_order');
    }

    public function label(?string $locale = null): string
    {
        return Interest::pickLocale($this->translations, $locale);
    }
}
