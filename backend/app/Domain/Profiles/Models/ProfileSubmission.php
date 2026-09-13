<?php

namespace App\Domain\Profiles\Models;

use App\Domain\People\Models\Person;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileSubmission extends Model
{
    use MassPrunable;

    protected $guarded = [];

    protected $casts = [
        'interest_codes'        => 'array',
        'birth_date'            => 'date',
        'birth_year_known'      => 'boolean',
        'consented_at'          => 'datetime',
        'accepted_at'           => 'datetime',
        'identity_confirmed_at' => 'datetime',
        'owner_notified_at'     => 'datetime',
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

    /**
     * Completările care se șterg singure, în fiecare noapte (docs/21; D-024):
     *  - cele la care proprietarul n-a ales „cine este?” în 30 de zile (M-10);
     *  - cele a căror persoană s-a șters definitiv (M-07), fiindcă datele trimise
     *    nu mai au la cine ajunge. Doar `AcceptSubmission` completează
     *    `accepted_at`, și o face odată cu `person_id`: o completare acceptată
     *    fără persoană înseamnă o persoană ștearsă.
     */
    public function prunable(): Builder
    {
        $cutoff = now()->subDays((int) config('wishio.retention.pending_submission_days'));

        return static::query()->where(fn (Builder $query) => $query
            ->where(fn (Builder $pending) => $pending->whereNull('accepted_at')->where('created_at', '<', $cutoff))
            ->orWhere(fn (Builder $orphaned) => $orphaned->whereNotNull('accepted_at')->whereNull('person_id')));
    }
}
