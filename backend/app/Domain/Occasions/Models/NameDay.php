<?php

namespace App\Domain\Occasions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NameDay extends Model
{
    protected $guarded = [];

    protected $casts = [
        'saint_name'  => 'array',
        'is_major'    => 'boolean',
        'is_verified' => 'boolean',
        'month'       => 'integer',
        'day'         => 'integer',
    ];

    public function aliases(): HasMany
    {
        return $this->hasMany(NameDayAlias::class);
    }

    /** Numele sfântului în limba cerută, cu fallback la RO. */
    public function saintName(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();

        return $this->saint_name[$locale]
            ?? $this->saint_name[config('wishio.locales.fallback')]
            ?? reset($this->saint_name);
    }
}
