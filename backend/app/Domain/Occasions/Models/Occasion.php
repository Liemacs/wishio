<?php

namespace App\Domain\Occasions\Models;

use App\Domain\People\Enums\FieldSource;
use App\Domain\People\Models\Person;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Occasion extends Model
{
    protected $guarded = [];

    protected $casts = [
        'source'       => FieldSource::class,
        'confidence'   => 'float',
        'confirmed_at' => 'datetime',
        'rejected_at'  => 'datetime',
        'is_muted'     => 'boolean',
        'month'        => 'integer',
        'day'          => 'integer',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function nameDay(): BelongsTo
    {
        return $this->belongsTo(NameDay::class);
    }

    public function holiday(): BelongsTo
    {
        return $this->belongsTo(Holiday::class);
    }

    public function isHoliday(): bool
    {
        return $this->holiday_id !== null;
    }

    /**
     * Persoanele pentru care are sens să cumperi de sărbătoarea asta.
     *
     * Se calculează la afișare, nu se stochează: lista de contacte se schimbă,
     * iar o listă înghețată ar deveni greșită fără să observe nimeni.
     */
    public function audience(): \Illuminate\Database\Eloquent\Collection
    {
        if (! $this->isHoliday()) {
            return new \Illuminate\Database\Eloquent\Collection();
        }

        return Person::query()
            ->where('user_id', $this->user_id)
            ->whereNull('archived_at')
            ->forAudience($this->holiday->audience)
            ->orderBy('display_name')
            ->get();
    }

    /**
     * Ocazia generează notificări doar dacă e sigură.
     *
     * Ce e confirmat de utilizator trece întotdeauna. Ce e dedus trebuie să
     * depășească pragul de încredere ȘI să provină dintr-o onomastică
     * verificată cu un calendar bisericesc. Vezi docs/15-onomastici.md § 4.
     */
    public function mayNotify(): bool
    {
        if ($this->is_muted || $this->rejected_at !== null) {
            return false;
        }

        if ($this->confirmed_at !== null) {
            return true;
        }

        if ($this->confidence < config('wishio.reminders.min_confidence_to_push')) {
            return false;
        }

        // Doar onomasticile cer verificare cu un calendar bisericesc;
        // sărbătorile sunt date publice, nu deduceri.
        return $this->type !== 'name_day' || (bool) $this->nameDay?->is_verified;
    }

    /** Următoarea apariție a ocaziei. */
    public function nextOccurrence(?CarbonImmutable $from = null): CarbonImmutable
    {
        $from = ($from ?? CarbonImmutable::today())->startOfDay();

        // Sărbătorile mobile (Paștele) nu se pot deduce din lună și zi:
        // data lor se schimbă în fiecare an. Întrebăm regula.
        if ($this->isHoliday()) {
            return $this->holiday->nextDate($from);
        }

        $next = CarbonImmutable::create($from->year, $this->month, $this->day);

        return $next->lessThan($from) ? $next->addYear() : $next;
    }

    /** Câte zile până la următoarea apariție. */
    public function daysUntil(?CarbonImmutable $from = null): int
    {
        $from = ($from ?? CarbonImmutable::today())->startOfDay();

        return (int) $from->diffInDays($this->nextOccurrence($from));
    }
}
