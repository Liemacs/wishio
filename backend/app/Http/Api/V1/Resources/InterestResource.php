<?php

namespace App\Http\Api\V1\Resources;

use App\Domain\People\Models\Interest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Interest */
class InterestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'code'          => $this->code,
            'label'         => $this->label(),
            'is_experience' => $this->is_experience,
            'confidence'    => $this->whenPivotLoaded('person_interests', fn () => (float) $this->pivot->confidence),
            'source'        => $this->whenPivotLoaded('person_interests', fn () => $this->pivot->source),
        ];
    }
}
