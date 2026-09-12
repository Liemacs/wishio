<?php

namespace App\Http\Api\V1\Controllers;

use App\Domain\People\Actions\MatchInterestsFromText;
use App\Domain\People\Models\Person;
use App\Http\Api\V1\Resources\InterestResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PersonAnalysisController extends Controller
{
    public function __construct(private readonly MatchInterestsFromText $match) {}

    /**
     * „Spune-mi despre Alex” → interese propuse. Ecranul P5 din docs/09.
     *
     * Rezultatul se PROPUNE, nu se aplică. Utilizatorul bifează ce e corect.
     * O deducere aplicată automat, fără confirmare, ar umple profilul cu
     * presupuneri pe care nimeni nu le-a verificat — iar recomandările
     * construite peste ele ar părea aleatorii fără motiv vizibil.
     *
     * Textul liber NU se stochează: poate conține date sensibile, iar nouă ne
     * trebuie doar rezultatul (docs/06 § 3).
     */
    public function store(Request $request, Person $person): JsonResponse
    {
        $this->authorize('update', $person);

        $data = $request->validate([
            'text' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        $matches = ($this->match)($data['text'], 8);

        return response()->json([
            'data' => [
                'suggestions' => $matches->map(fn (array $row) => [
                    'interest'   => InterestResource::make($row['interest'])->resolve($request),
                    'confidence' => $row['confidence'],
                    'matched'    => $row['matched'],
                ])->values(),
            ],
        ]);
    }
}
