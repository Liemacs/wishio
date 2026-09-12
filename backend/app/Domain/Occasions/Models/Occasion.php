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

        return $this->type !== 'name_day' || (bool) $this->nameDay?->is_verified;
    }

    /** Câte zile până la următoarea apariție, de la o dată dată. */
    public function daysUntil(?CarbonImmutable $from = null): int
    {
        $from = ($from ?? CarbonImmutable::today())->startOfDay();
        $next = CarbonImmutable::create($from->year, $this->month, $this->day);

        if ($next->lessThan($from)) {
            $next = $next->addYear();
        }

        return (int) $from->diffInDays($next);
    }
}
