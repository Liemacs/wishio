<?php

namespace App\Http\Api\V1\Resources;

use App\Domain\People\Models\GiftIdea;
use App\Domain\Recommendations\Models\RecommendationRun;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin RecommendationRun */
class RecommendationRunResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'     => $this->id,
            'status' => $this->status,
            'kind'   => $this->kind,
            // Clientul trebuie să știe dacă au fost sau nu explicații: fără
            // consimțământ AI produsele apar fără motivație, și asta se vede.
            'has_ai'   => $this->provider !== null && $this->provider !== 'rules',
            'criteria' => $this->criteria,

            'items' => $this->whenLoaded('items', function () {
                // Ce e deja salvat pentru această persoană: rezultatele arată „Salvată”.
                $saved = GiftIdea::where('person_id', $this->person_id)
                    ->whereNotNull('product_id')
                    ->pluck('id', 'product_id');

                return $this->items->map(fn ($item) => [
                    'rank'    => $item->rank,
                    'score'   => $item->score,
                    'reason'  => $item->reason,
                    'idea_id' => $saved[$item->product_id] ?? null,
                    'product' => ProductResource::make($item->product),
                ]);
            }),
        ];
    }
}
