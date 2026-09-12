<?php

namespace App\Http\Api\V1\Resources;

use App\Domain\Occasions\Models\Occasion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Occasion */
class OccasionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'    => $this->id,
            'type'  => $this->type,
            'label' => $this->holiday?->label()
                ?? $this->title
                ?? __("wishio.occasions.{$this->type}"),
            'month'      => $this->month,
            'day'        => $this->day,
            'year'       => $this->year,
            'days_until' => $this->daysUntil(),
            'source'     => $this->source->value,
            'confidence' => $this->confidence,
            'confirmed'  => $this->confirmed_at !== null,
            'rejected'   => $this->rejected_at !== null,
            'is_muted'   => $this->is_muted,
            'may_notify' => $this->mayNotify(),

            'saint_name' => $this->whenLoaded('nameDay', fn () => $this->nameDay?->saintName()),

            // Publicul unei sărbători se calculează la afișare: lista de
            // contacte se schimbă, iar una înghețată ar deveni greșită.
            'audience' => $this->when($this->isHoliday(), fn () => $this->audience()
                ->map(fn ($person) => ['id' => $person->id, 'display_name' => $person->display_name])
                ->values()),

            'person' => $this->whenLoaded('person', fn () => [
                'id'           => $this->person->id,
                'display_name' => $this->person->display_name,
            ]),
        ];
    }
}
