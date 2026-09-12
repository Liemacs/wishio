<?php

namespace App\Domain\Occasions\Actions;

use App\Domain\Occasions\DTOs\NameDayMatch;
use App\Domain\Occasions\Models\NameDay;
use App\Support\Names\NameNormalizer;
use Illuminate\Support\Collection;

/**
 * Deduce onomastica dintr-un nume de contact.
 *
 * Este cel mai important mecanism de cold-start al produsului: din 200 de contacte,
 * poate 8 au ziua de nastere completata, dar ~150 au prenume.
 * Vezi docs/02-strategie-riscuri.md § R1 si docs/04-model-domeniu.md § 4.
 */
class ResolveNameDay
{
    public function __construct(private readonly NameNormalizer $normalizer) {}

    /**
     * Toate potrivirile pentru un nume de contact, cea mai buna prima.
     *
     * @return Collection<int, NameDayMatch>
     */
    public function all(
        string $contactName,
        string $countryCode = 'MD',
        string $calendar = 'orthodox_new',
    ): Collection {
        $weights = $this->normalizer->candidateWeights($contactName);

        if ($weights === []) {
            return collect();
        }

        $rows = NameDay::query()
            ->where('country_code', $countryCode)
            ->where('calendar', $calendar)
            ->join('name_day_aliases', 'name_days.id', '=', 'name_day_aliases.name_day_id')
            ->whereIn('name_day_aliases.normalized', array_keys($weights))
            ->select([
                'name_days.*',
                'name_day_aliases.given_name as alias_name',
                'name_day_aliases.normalized as alias_normalized',
                'name_day_aliases.confidence as alias_confidence',
                'name_day_aliases.is_diminutive as alias_is_diminutive',
            ])
            ->get();

        return $rows
            ->map(function (NameDay $row) use ($weights): NameDayMatch {
                // increderea aliasului x cat de sigur e ca tokenul potrivit
                // chiar este prenumele persoanei
                $confidence = (float) $row->alias_confidence
                    * ($weights[$row->alias_normalized] ?? 0.8);

                return new NameDayMatch(
                    nameDay: $row,
                    matchedName: $row->alias_name,
                    confidence: round($confidence, 2),
                    isDiminutive: (bool) $row->alias_is_diminutive,
                );
            })
            ->sortByDesc(fn (NameDayMatch $m) => [$m->confidence, $m->nameDay->is_major])
            ->values();
    }

    public function best(
        string $contactName,
        string $countryCode = 'MD',
        string $calendar = 'orthodox_new',
    ): ?NameDayMatch {
        return $this->all($contactName, $countryCode, $calendar)->first();
    }
}
