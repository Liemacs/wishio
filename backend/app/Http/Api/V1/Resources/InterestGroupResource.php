<?php

namespace App\Http\Api\V1\Resources;

use App\Domain\People\Models\InterestGroup;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin InterestGroup */
class InterestGroupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'code'      => $this->code,
            'label'     => $this->label(),
            'icon'      => $this->icon,
            'interests' => InterestResource::collection($this->whenLoaded('interests')),
        ];
    }
}
