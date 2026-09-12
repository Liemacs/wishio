<?php

namespace App\Domain\Recommendations\Jobs;

use App\Domain\Recommendations\Actions\GenerateRecommendations;
use App\Domain\Recommendations\Models\RecommendationRun;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Generarea nu blochează niciodată request-ul (regula 8 din CLAUDE.md).
 * Clientul primește o rulare `pending` și întreabă până devine `ready`.
 */
class GenerateRecommendationsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly int $runId) {}

    public function handle(GenerateRecommendations $generate): void
    {
        $run = RecommendationRun::with('person')->find($this->runId);

        if (! $run || $run->status !== 'pending') {
            return;
        }

        $generated = $generate($run->person, $run->budget_min, $run->budget_max);

        // Acțiunea creează propria rulare; o legăm de cea cerută de client
        // și o pe cea intermediară o scoatem.
        $run->update([
            'status'   => $generated->status,
            'criteria' => $generated->criteria,
            'provider' => $generated->provider,
            'failure'  => $generated->failure,
        ]);

        $generated->items()->update(['recommendation_run_id' => $run->id]);
        $generated->delete();
    }
}
