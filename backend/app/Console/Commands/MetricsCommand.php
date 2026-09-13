<?php

namespace App\Console\Commands;

use App\Domain\Metrics\Actions\BuildMetricsReport;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class MetricsCommand extends Command
{
    protected $signature = 'wishio:metrics
                            {--since= : Cohorta: conturile create de la această dată (implicit, acum 30 de zile)}
                            {--until= : Până la această dată (implicit, azi)}';

    protected $description = 'Pâlnia G3–G5 și tabloul săptămânal din docs/07, calculate din baza de date';

    private const LABELS = [
        'accounts'        => 'Conturi create',
        'people_5'        => 'Cu cel puțin 5 persoane (G3)',
        'occasions_8'     => 'Cu cel puțin 8 ocazii',
        'push_enabled'    => 'Cu notificările pornite',
        'reminder_sent'   => 'Au primit un reminder',
        'reminder_opened' => 'Remindere deschise, din cele trimise (G4)',
        'recommendations' => 'Au cerut idei de cadou (G5)',
        'clicks'          => 'Au deschis un magazin (G5)',
        'gift_given'      => 'Au marcat un cadou ca oferit',
    ];

    public function handle(BuildMetricsReport $report): int
    {
        $until = $this->option('until') ? CarbonImmutable::parse($this->option('until'))->endOfDay() : CarbonImmutable::now();
        $since = $this->option('since') ? CarbonImmutable::parse($this->option('since'))->startOfDay() : $until->subDays(30)->startOfDay();

        $this->components->info(sprintf(
            'Cohorta: conturile create între %s și %s. Conturile %s nu intră.',
            $since->toDateString(), $until->toDateString(), BuildMetricsReport::INTERNAL_DOMAIN,
        ));

        $this->table(['Pas', 'Câți', 'Din', 'Rată', 'Țintă'], array_map(fn (array $step) => [
            self::LABELS[$step['key']],
            $step['count'],
            $step['of'],
            $step['of'] > 0 ? $this->percent($step['count'] / $step['of']) : '—',
            $step['target'] !== null ? '> '.$this->percent($step['target']) : '',
        ], $report->funnel($since, $until)));

        $weekly = $report->weekly(CarbonImmutable::now());

        $this->components->info('Ultimele 7 zile (docs/07 § 6)');

        $this->table(['Cifra', 'Valoare'], [
            ['Utilizatori activi', $weekly['active_users']],
            ['Persoane pentru care s-a făcut ceva', $weekly['people_acted_for']],
            ['Clickuri spre magazine', $weekly['clicks']],
            ['Completări prin linkuri personale (instalările nu se măsoară)', $weekly['link_submissions']],
            ['Retenție D30, cohorta de acum 30–60 de zile', $weekly['retention_cohort'] > 0
                ? sprintf('%s (%d din %d)', $this->percent($weekly['retention_d30'] / $weekly['retention_cohort']), $weekly['retention_d30'], $weekly['retention_cohort'])
                : '—'],
        ]);

        return self::SUCCESS;
    }

    private function percent(float $rate): string
    {
        return number_format($rate * 100).'%';
    }
}
