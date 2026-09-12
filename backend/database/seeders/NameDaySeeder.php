<?php

namespace Database\Seeders;

use App\Domain\Occasions\Models\NameDay;
use App\Support\Names\NameNormalizer;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NameDaySeeder extends Seeder
{
    /**
     * In Moldova coexista doua calendare ortodoxe. Sarbatorile fixe pe stil vechi
     * cad la data pe stil nou + 13 zile (Sf. Nicolae 6 dec -> 19 dec,
     * Sf. Gheorghe 23 apr -> 6 mai, Sf. Tatiana 12 ian -> 25 ian).
     * Generam varianta a doua in loc s-o scriem de mana.
     */
    private const OLD_STYLE_OFFSET_DAYS = 13;

    public function run(): void
    {
        $entries    = require database_path('seeders/data/name_days.php');
        $normalizer = new NameNormalizer();

        DB::transaction(function () use ($entries, $normalizer) {
            foreach ($entries as $entry) {
                foreach (['orthodox_new', 'orthodox_old'] as $calendar) {
                    [$month, $day] = $calendar === 'orthodox_new'
                        ? [$entry['month'], $entry['day']]
                        : $this->toOldStyle($entry['month'], $entry['day']);

                    $nameDay = NameDay::updateOrCreate(
                        [
                            'code'         => $entry['code'],
                            'country_code' => 'MD',
                            'calendar'     => $calendar,
                        ],
                        [
                            'month'       => $month,
                            'day'         => $day,
                            'saint_name'  => $entry['saint'],
                            'is_major'    => $entry['major'],
                            'is_verified' => false,
                            'source'      => $calendar === 'orthodox_new'
                                ? 'seed initial — DE VERIFICAT cu calendar bisericesc'
                                : 'derivat din stil nou + 13 zile — DE VERIFICAT',
                        ]
                    );

                    $rows = [];
                    foreach ($entry['names'] as $alias) {
                        $normalized = $normalizer->normalize($alias['name']);

                        if ($normalized === '' || isset($rows[$normalized])) {
                            continue;   // evita duplicatele din sursa
                        }

                        $rows[$normalized] = [
                            'name_day_id'   => $nameDay->id,
                            'given_name'    => $alias['name'],
                            'normalized'    => $normalized,
                            'gender'        => $alias['gender'],
                            'script'        => $alias['script'],
                            'confidence'    => $alias['confidence'],
                            'is_diminutive' => $alias['diminutive'],
                            'created_at'    => now(),
                            'updated_at'    => now(),
                        ];
                    }

                    $nameDay->aliases()->delete();
                    DB::table('name_day_aliases')->insert(array_values($rows));
                }
            }
        });

        $this->command?->info(sprintf(
            'Onomastici: %d intrari (%d stil nou + %d stil vechi), %d aliasuri.',
            NameDay::count(),
            NameDay::where('calendar', 'orthodox_new')->count(),
            NameDay::where('calendar', 'orthodox_old')->count(),
            DB::table('name_day_aliases')->count(),
        ));

        $this->command?->warn(
            'Toate sunt is_verified = false. Nu genereaza push pana la verificare — docs/15-onomastici.md.'
        );
    }

    /** @return array{int,int} */
    private function toOldStyle(int $month, int $day): array
    {
        // 2025 e an nebisect; datele noastre nu contin 29 februarie.
        $date = CarbonImmutable::create(2025, $month, $day)->addDays(self::OLD_STYLE_OFFSET_DAYS);

        return [$date->month, $date->day];
    }
}
