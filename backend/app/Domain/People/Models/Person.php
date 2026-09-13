<?php

namespace App\Domain\People\Models;

use App\Domain\Occasions\Models\Occasion;
use App\Domain\People\Enums\FieldSource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
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
    use MassPrunable, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'birth_date'       => 'date',
        'birth_year_known' => 'boolean',
        'archived_at'      => 'datetime',
        'from_public_link' => 'boolean',
        // Notele pot conține date sensibile („e diabetic”, „divorțează”).
        // Criptate la rest; nu se trimit niciodată spre AI (docs/05 § 5).
        'notes' => 'encrypted',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

    public function occasions(): HasMany
    {
        return $this->hasMany(Occasion::class);
    }

    public function giftHistory(): HasMany
    {
        return $this->hasMany(GiftHistory::class);
    }

    public function giftIdeas(): HasMany
    {
        return $this->hasMany(GiftIdea::class);
    }

    /**
     * O persoană ștearsă rămâne 30 de zile, ca un reimport din agendă s-o
     * readucă cu tot cu note (`ImportContacts`), apoi se șterge definitiv
     * (docs/21, M-07; D-024). Ștergerea în masă trece pe lângă evenimente, dar
     * nu e nevoie de ele: ocaziile, reminderele, interesele, ideile și istoricul
     * pleacă în cascadă, prin cheile străine.
     */
    public function prunable(): Builder
    {
        return static::onlyTrashed()->where(
            'deleted_at',
            '<',
            now()->subDays((int) config('wishio.retention.deleted_people_days')),
        );
    }

    /**
     * Persoanele cărora li se potrivește publicul unei sărbători.
     *
     * E un filtru de comoditate, nu o regulă: utilizatorul vede oricum lista
     * și poate cumpăra pentru oricine. Pentru „copii” ne bazăm pe relație
     * înaintea vârstei, fiindcă vârsta lipsește foarte des.
     */
    public function scopeForAudience(Builder $query, string $audience): void
    {
        match ($audience) {
            'women'    => $query->where('gender', 'f'),
            'men'      => $query->where('gender', 'm'),
            'partner'  => $query->where('relationship', 'partner'),
            'children' => $query->where(fn ($q) => $q
                ->where('relationship', 'child')
                ->orWhere(fn ($age) => $age
                    ->where('birth_year_known', true)
                    ->where('birth_date', '>', now()->subYears(14)))),
            default => null,
        };
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
