<?php

namespace App\Http\Api\V1\Resources;

use App\Domain\Recommendations\Models\RecommendationRun;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin RecommendationRun */
class RecommendationRunResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'       => $this->id,
            'status'   => $this->status,
            'kind'     => $this->kind,
            // Clientul trebuie să știe dacă au fost sau nu explicații: fără
            // consimțământ AI produsele apar fără motivație, și asta se vede.
            'has_ai'   => $this->provider !== null && $this->provider !== 'rules',
            'criteria' => $this->criteria,

            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'rank'    => $item->rank,
                'score'   => $item->score,
                'reason'  => $item->reason,
                'product' => ProductResource::make($item->product),
            ])),
        ];
    }
}
