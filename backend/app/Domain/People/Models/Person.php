<?php

namespace App\Domain\People\Models;

use App\Domain\People\Enums\FieldSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * O persoană din viața unui utilizator. NU are cont și NU este partajată.
 *
 * Person ≠ User. Vezi docs/04-model-domeniu.md § 1 și regula 3 din CLAUDE.md:
 * nicio dată de aici nu ajunge la alt utilizator fără consimțământul persoanei.
 */
class Person extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'birth_date'       => 'date',
        'birth_year_known' => 'boolean',
        'archived_at'      => 'datetime',
        // Notele pot conține date sensibile („e diabetic”, „divorțează”).
        // Criptate la rest; nu se trimit niciodată spre AI (docs/05 § 5).
        'notes'            => 'encrypted',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function fieldSources(): HasMany
    {
        return $this->hasMany(PersonFieldSource::class);
    }

    public function interests(): BelongsToMany
    {
        return $this->belongsToMany(Interest::class, 'person_interests')
            ->withPivot(['source', 'confidence'])
            ->withTimestamps();
    }

    public function avoids(): HasMany
    {
        return $this->hasMany(PersonAvoid::class);
    }

    /** Sursa înregistrată pentru un câmp, dacă există. */
    public function sourceFor(string $field): ?PersonFieldSource
    {
        return $this->fieldSources->firstWhere('field', $field)
            ?? $this->fieldSources()->where('field', $field)->first();
    }

    /** Câmpul a fost modificat manual și e protejat de orice sincronizare. */
    public function isOverridden(string $field): bool
    {
        return $this->sourceFor($field)?->overridden_at !== null;
    }

    /** Vârsta, doar dacă știm și anul — deseori avem doar ziua și luna. */
    public function age(): ?int
    {
        return $this->birth_date && $this->birth_year_known
            ? $this->birth_date->age
            : null;
    }

    /** Nivelul de încredere al valorii curente a câmpului. */
    public function trustLevel(string $field): ?FieldSource
    {
        // `source` e deja convertit în enum de cast-ul din PersonFieldSource.
        return $this->sourceFor($field)?->source;
    }
}
