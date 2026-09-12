<?php

namespace App\Domain\Occasions\Models;

use App\Domain\People\Models\Interest;
use App\Support\Calendar\OrthodoxEaster;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $guarded = [];

    protected $casts = [
        'translations'  => 'array',
        'reminder_days' => 'array',
        'is_active'     => 'boolean',
    ];

    public function label(?string $locale = null): string
    {
        return Interest::pickLocale($this->translations, $locale);
    }

    /** Data sărbătorii într-un an dat. */
    public function dateIn(int $year): CarbonImmutable
    {
        return $this->date_rule === 'easter'
            ? OrthodoxEaster::for($year)->addDays($this->easter_offset_days)
            : CarbonImmutable::create($year, $this->month, $this->day);
    }

    /** Următoarea apariție, de la o dată dată. */
    public function nextDate(CarbonImmutable $from): CarbonImmutable
    {
        $thisYear = $this->dateIn($from->year);

        return $thisYear->endOfDay()->lessThan($from)
            ? $this->dateIn($from->year + 1)
            : $thisYear;
    }
}
