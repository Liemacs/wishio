<?php

namespace App\Domain\Profiles\Models;

use App\Domain\People\Models\Person;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileSubmission extends Model
{
    protected $guarded = [];

    protected $casts = [
        'interest_codes'        => 'array',
        'birth_date'            => 'date',
        'birth_year_known'      => 'boolean',
        'consented_at'          => 'datetime',
        'accepted_at'           => 'datetime',
        'identity_confirmed_at' => 'datetime',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(PublicProfile::class, 'public_profile_id');
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    /** Completările care așteaptă alegerea proprietarului: „cine este?”. */
    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('accepted_at');
    }
}
