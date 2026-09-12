<?php

namespace App\Http\Api\V1\Resources;

use App\Domain\People\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Person */
class PersonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'display_name'     => $this->display_name,
            'relationship'     => $this->relationship,
            'gender'           => $this->gender,
            'birth_date'       => $this->birth_date?->format('Y-m-d'),
            'birth_year_known' => $this->birth_year_known,
            'age'              => $this->age(),
            'budget_min'       => $this->budget_min,
            'budget_max'       => $this->budget_max,
            'notes'            => $this->notes,
            'avatar_path'      => $this->avatar_path,

            // Clientul trebuie sa stie ce e dedus si ce e confirmat, ca sa
            // marcheze vizual si sa nu propuna reconfirmarea a ce e deja sigur.
            'trust' => $this->whenLoaded('fieldSources', fn () => $this->fieldSources
                ->mapWithKeys(fn ($s) => [$s->field => [
                    'source'     => $s->source->value,
                    'confidence' => $s->confidence,
                    'overridden' => $s->overridden_at !== null,
                ]])),

            'interests' => InterestResource::collection($this->whenLoaded('interests')),

            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
