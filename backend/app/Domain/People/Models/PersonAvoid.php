<?php

namespace App\Domain\People\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Semnal negativ: „fără haine”, „nu bea alcool”, „e vegetarian”.
 * Valorează cât cele pozitive — un cadou nepotrivit strică mai mult
 * decât ajută zece potrivite.
 */
class PersonAvoid extends Model
{
    protected $guarded = [];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function interest(): BelongsTo
    {
        return $this->belongsTo(Interest::class);
    }
}
