<?php

namespace App\Http\Api\V1\Resources;

use App\Domain\Catalog\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Product */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $best   = $this->bestOffer();
        $inStock = $this->offers->where('in_stock', true);

        return [
            'id'         => $this->id,
            'title'      => $this->title,
            'brand'      => $this->brand,
            'image_url'  => $this->image_url,
            'category'   => $this->whenLoaded('category', fn () => $this->category?->label()),
            'gift_score' => $this->gift_score,

            'price'     => $best ? (float) $best->price : null,
            'old_price' => $best?->old_price ? (float) $best->old_price : null,
            'currency'  => $best?->currency ?? 'MDL',

            'offer' => $best ? [
                'id'       => $best->id,
                'merchant' => $best->merchant->name,
            ] : null,

            // „Vezi la alte N magazine”: același produs, prețuri diferite.
            // Fără el, lista ar arăta de trei ori aceleași căști.
            'other_offers_count' => max(0, $inStock->count() - 1),

            'interests' => InterestResource::collection($this->whenLoaded('interests')),
        ];
    }
}
