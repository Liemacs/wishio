<?php

namespace App\Http\Api\V1\Controllers;

use App\Domain\People\Models\Person;
use App\Domain\Recommendations\Jobs\GenerateRecommendationsJob;
use App\Domain\Recommendations\Models\RecommendationRun;
use App\Http\Api\V1\Resources\RecommendationRunResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecommendationController extends Controller
{
    public function store(Request $request, Person $person): JsonResponse
    {
        $this->authorize('view', $person);

        $data = $request->validate([
            'budget_min'  => ['nullable', 'integer', 'min:0'],
            'budget_max'  => ['nullable', 'integer', 'min:0', 'gte:budget_min'],
            'occasion_id' => ['nullable', 'integer', 'exists:occasions,id'],
        ]);

        $run = RecommendationRun::create([
            'user_id'     => $request->user()->id,
            'person_id'   => $person->id,
            'occasion_id' => $data['occasion_id'] ?? null,
            'budget_min'  => $data['budget_min'] ?? $person->budget_min,
            'budget_max'  => $data['budget_max'] ?? $person->budget_max,
            'kind'        => 'gift',
            'locale'      => $request->user()->locale,
            'status'      => 'pending',
        ]);

        GenerateRecommendationsJob::dispatch($run->id);

        return RecommendationRunResource::make($run->fresh(['items.product.offers.merchant']))
            ->response()
            ->setStatusCode(202);
    }

    public function show(Request $request, RecommendationRun $recommendation): RecommendationRunResource
    {
        abort_unless($recommendation->user_id === $request->user()->id, 403);

        return RecommendationRunResource::make(
            $recommendation->load(['items.product.offers.merchant', 'items.product.category', 'items.product.interests'])
        );
    }

    /**
     * Consimțământul de a trimite context către un AI terț.
     *
     * Apple îl cere explicit din 13 noiembrie 2025 (docs/06 § 2). Aplicația
     * funcționează integral și fără el — altfel „consimțământul” ar fi o
     * formalitate, nu o alegere.
     */
    public function consent(Request $request): JsonResponse
    {
        $data = $request->validate(['granted' => ['required', 'boolean']]);

        $request->user()->update(['ai_consent_at' => $data['granted'] ? now() : null]);

        return response()->json(['data' => ['ai_consent' => $data['granted']]]);
    }
}
