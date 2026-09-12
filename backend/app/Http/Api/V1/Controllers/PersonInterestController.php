<?php

namespace App\Http\Api\V1\Controllers;

use App\Domain\People\Enums\FieldSource;
use App\Domain\People\Models\Interest;
use App\Domain\People\Models\Person;
use App\Http\Api\V1\Resources\PersonResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PersonInterestController extends Controller
{
    /**
     * Inlocuieste interesele alese manual de utilizator.
     *
     * Interesele deduse (AI sau text liber) NU se sterg aici: utilizatorul
     * bifeaza ce stie el, iar restul raman ca semnale cu incredere mai mica.
     */
    public function update(Request $request, Person $person): PersonResource
    {
        $this->authorize('update', $person);

        $data = $request->validate([
            'codes'   => ['present', 'array'],
            'codes.*' => ['string', Rule::in(Interest::validCodes())],
        ]);

        $ids = Interest::whereIn('code', $data['codes'])->pluck('id');

        $manual = $person->interests()
            ->wherePivot('source', FieldSource::OwnerManual->value)
            ->pluck('interests.id');

        $person->interests()->detach($manual);

        $person->interests()->syncWithoutDetaching(
            $ids->mapWithKeys(fn (int $id) => [$id => [
                'source'     => FieldSource::OwnerManual->value,
                'confidence' => FieldSource::OwnerManual->defaultConfidence(),
            ]])->all()
        );

        return PersonResource::make($person->fresh(['interests', 'fieldSources']));
    }
}
