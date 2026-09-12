<?php

namespace App\Console\Commands;

use App\Domain\People\Actions\MatchInterestsFromText;
use App\Domain\Validation\Models\GiftRequest;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;

/**
 * Unealta de lucru pentru Faza 0 (docs/14 § 4).
 * Fara autentificare, fara interfata — ruleaza local, pe datele tale.
 */
class GiftRequestsCommand extends Command
{
    protected $signature = 'wishio:requests
                            {--pending : Doar cererile la care nu ai raspuns}
                            {--answer= : ID-ul cererii pe care o marchezi ca rezolvata}
                            {--stats : Porțile G0 si G1}';

    protected $description = 'Cererile din Faza 0: lista, raspuns, statistici';

    public function handle(MatchInterestsFromText $match): int
    {
        if ($this->option('stats')) {
            return $this->stats();
        }

        if ($id = $this->option('answer')) {
            return $this->answer((int) $id);
        }

        return $this->list($match);
    }

    private function list(MatchInterestsFromText $match): int
    {
        $requests = GiftRequest::query()
            ->when($this->option('pending'), fn ($q) => $q->whereNull('answered_at'))
            ->latest()
            ->limit(30)
            ->get();

        if ($requests->isEmpty()) {
            $this->info('Nicio cerere.');

            return self::SUCCESS;
        }

        foreach ($requests as $request) {
            $this->newLine();
            $this->line(sprintf(
                '<fg=white;bg=magenta> #%d </> %s · %s · %s · %s%s',
                $request->id,
                strtoupper($request->locale),
                __('landing.relationships.'.$request->relationship),
                $request->age_bracket ?? '?',
                $this->budget($request),
                $request->isAnswered() ? '  <fg=green>✓ răspuns</>' : '  <fg=yellow>● în așteptare</>'
            ));

            $this->line('  <fg=gray>'.$request->contact.'</>'
                .($request->occasion ? '  ·  '.__('landing.occasions.'.$request->occasion) : '')
                .($request->occasion_date ? ' '.$request->occasion_date->format('d.m.Y') : ''));

            $this->newLine();
            $this->line('  '.wordwrap($request->about, 90, PHP_EOL.'  '));

            // Ce ar deduce produsul singur — util ca punct de plecare la cautare
            // si ca material pentru calibrarea taxonomiei (docs/16 § 5).
            $interests = $match($request->about, 5);
            if ($interests->isNotEmpty()) {
                $this->newLine();
                $this->line('  <fg=cyan>interese deduse:</> '.$interests
                    ->map(fn ($i) => sprintf('%s (%.2f)', $i['interest']->label('ro'), $i['confidence']))
                    ->implode(' · '));
            }

            if ($request->feedback) {
                $this->newLine();
                $this->line('  <fg=green>feedback:</> '.$request->feedback);
            }
        }

        $this->newLine();

        return self::SUCCESS;
    }

    private function answer(int $id): int
    {
        $request = GiftRequest::find($id);

        if (! $request) {
            $this->error("Cererea #$id nu există.");

            return self::FAILURE;
        }

        $this->line('  '.wordwrap($request->about, 90, PHP_EOL.'  '));
        $this->newLine();

        $request->update([
            'answered_at'     => now(),
            'sent_ideas'      => text('Ce i-ai trimis? (produs — preț — magazin, separate prin ;)'),
            'minutes_spent'   => (int) text('Cât timp ți-a luat căutarea, în minute?', default: '30'),
            'feedback'        => text('Ce a răspuns? (lasă gol dacă încă nu a răspuns)', required: false),
            'would_buy'       => confirm('A spus că ar cumpăra ceva din ce i-ai trimis?', default: false),
            'clicked_through' => confirm('A accesat vreun link spre magazin?', default: false),
        ]);

        $this->info("Cererea #$id actualizată.");

        return self::SUCCESS;
    }

    private function stats(): int
    {
        $total = GiftRequest::count();
        $answered = GiftRequest::whereNotNull('answered_at')->count();
        $wouldBuy = GiftRequest::where('would_buy', true)->count();
        $clicked = GiftRequest::where('clicked_through', true)->count();
        $minutes = (int) GiftRequest::whereNotNull('minutes_spent')->avg('minutes_spent');

        $this->newLine();
        $this->line('<options=bold>Porțile Faza 0</> — docs/02 § 3');
        $this->newLine();

        $this->table(
            ['Poartă', 'Criteriu', 'Acum', 'Stare'],
            [
                ['G0', '20+ cereri primite', $total, $this->gate($total >= 20)],
                ['G1a', '10+ spun „aș cumpăra"', $wouldBuy, $this->gate($wouldBuy >= 10)],
                ['G1b', '5+ au dat click', $clicked, $this->gate($clicked >= 5)],
            ]
        );

        $this->line("  Răspunse: $answered / $total   ·   Timp mediu de căutare: {$minutes} min");

        // Semnalul care decide V1 vs V2 din docs/14 § 3.
        if ($minutes > 30 && $answered >= 8) {
            $this->newLine();
            $this->warn('  Peste 30 min per cerere după 8 livrate — unealta din docs/14 § 3 (V2) devine justificată.');
        }

        $this->newLine();
        $this->line('  Pe limbi: '.GiftRequest::selectRaw('locale, count(*) c')
            ->groupBy('locale')->pluck('c', 'locale')
            ->map(fn ($c, $l) => strtoupper($l)." $c")->implode('  ·  '));
        $this->newLine();

        return self::SUCCESS;
    }

    private function budget(GiftRequest $r): string
    {
        return match (true) {
            $r->budget_min && $r->budget_max => "{$r->budget_min}–{$r->budget_max} MDL",
            (bool) $r->budget_max            => "<{$r->budget_max} MDL",
            (bool) $r->budget_min            => ">{$r->budget_min} MDL",
            default                          => 'buget nespecificat',
        };
    }

    private function gate(bool $passed): string
    {
        return $passed ? '<fg=green>✓</>' : '<fg=yellow>—</>';
    }
}
