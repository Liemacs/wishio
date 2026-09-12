<?php

namespace App\Support\Calendar;

use Carbon\CarbonImmutable;

/**
 * Data Paștelui ortodox, în calendarul civil.
 *
 * Algoritmul lui Meeus pentru calendarul iulian, apoi decalajul de 13 zile
 * către gregorian (valabil 1900–2099). Paștele e cea mai importantă
 * sărbătoare mobilă din Moldova și nu poate fi pus într-un tabel de date fixe.
 *
 * Verificat pe ani cunoscuți: 2024 → 5 mai, 2025 → 20 aprilie, 2026 → 12 aprilie.
 */
class OrthodoxEaster
{
    /** Decalajul iulian → gregorian pentru secolele XX–XXI. */
    private const JULIAN_OFFSET_DAYS = 13;

    public static function for(int $year): CarbonImmutable
    {
        $a = $year % 4;
        $b = $year % 7;
        $c = $year % 19;
        $d = (19 * $c + 15) % 30;
        $e = (2 * $a + 4 * $b - $d + 34) % 7;

        $month = intdiv($d + $e + 114, 31);
        $day   = (($d + $e + 114) % 31) + 1;

        return CarbonImmutable::create($year, $month, $day)
            ->addDays(self::JULIAN_OFFSET_DAYS);
    }
}
