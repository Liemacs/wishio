<?php

namespace Database\Seeders;

use App\Domain\Occasions\Models\Holiday;
use Illuminate\Database\Seeder;

class HolidaySeeder extends Seeder
{
    public function run(): void
    {
        foreach (require database_path('seeders/data/holidays.php') as $entry) {
            Holiday::updateOrCreate(
                ['country_code' => 'MD', 'code' => $entry['code']],
                [
                    'translations'       => ['ro' => $entry['ro'], 'ru' => $entry['ru'], 'en' => $entry['en']],
                    'date_rule'          => $entry['rule'],
                    'month'              => $entry['month'] ?? null,
                    'day'                => $entry['day'] ?? null,
                    'easter_offset_days' => $entry['offset'] ?? 0,
                    'audience'           => $entry['audience'],
                    'reminder_days'      => $entry['reminder_days'] ?? null,
                ]
            );
        }

        $this->command?->info(sprintf('Sărbători: %d (%d mobile).',
            Holiday::count(), Holiday::where('date_rule', 'easter')->count()));
    }
}
