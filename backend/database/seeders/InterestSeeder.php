<?php

namespace Database\Seeders;

use App\Domain\People\Models\Interest;
use App\Domain\People\Models\InterestGroup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InterestSeeder extends Seeder
{
    public function run(): void
    {
        $groups = require database_path('seeders/data/interests.php');

        DB::transaction(function () use ($groups) {
            foreach ($groups as $gi => $group) {
                $model = InterestGroup::updateOrCreate(
                    ['code' => $group['code']],
                    [
                        'translations' => ['ro' => $group['ro'], 'ru' => $group['ru'], 'en' => $group['en']],
                        'icon'         => $group['icon'],
                        'sort_order'   => $gi,
                    ]
                );

                foreach ($group['interests'] as $ii => $interest) {
                    Interest::updateOrCreate(
                        ['code' => $interest['code']],
                        [
                            'interest_group_id' => $model->id,
                            'translations'      => ['ro' => $interest['ro'], 'ru' => $interest['ru'], 'en' => $interest['en']],
                            'keywords'          => $interest['kw'],
                            'gender_affinity'   => $interest['gender'],
                            'typical_min_price' => $interest['price'][0] ?? null,
                            'typical_max_price' => $interest['price'][1] ?? null,
                            'is_experience'     => $interest['experience'],
                            'sort_order'        => $ii,
                        ]
                    );
                }
            }
        });

        cache()->forget('interests.codes');

        $this->command?->info(sprintf(
            'Interese: %d grupuri, %d frunze (%d experiente), %d cuvinte-cheie.',
            InterestGroup::count(),
            Interest::count(),
            Interest::where('is_experience', true)->count(),
            Interest::all()->sum(fn (Interest $i) => array_sum(array_map('count', $i->keywords))),
        ));
    }
}
