<?php

namespace App\Http\Api\V1\Controllers;

use App\Domain\People\Actions\WritePersonField;
use App\Domain\People\Enums\FieldSource;
use App\Domain\People\Models\Person;
use App\Http\Api\V1\Requests\SavePersonRequest;
use App\Http\Api\V1\Resources\PersonResource;
use App\Http\Controllers\Controller;
use App\Support\Names\NameNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PersonController extends Controller
{
    public function __construct(
        private readonly WritePersonField $writeField,
        private readonly NameNormalizer $normalizer,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $people = $request->user()->people()
            ->whereNull('archived_at')
            ->with(['interests', 'fieldSources'])
            ->orderBy('display_name')
            ->get();

        return PersonResource::collection($people);
    }

    public function store(SavePersonRequest $request): JsonResponse
    {
        $data = $request->validated();

        $person = $request->user()->people()->create([
            'display_name'          => $data['display_name'],
            'given_name_normalized' => $this->firstName($data['display_name']),
        ]);

        $this->applyFields($person, $data);

        return PersonResource::make($person->load(['interests', 'fieldSources']))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Person $person): PersonResource
    {
        $this->authorize('view', $person);

        return PersonResource::make($person->load(['interests', 'fieldSources', 'avoids']));
    }

    public function update(SavePersonRequest $request, Person $person): PersonResource
    {
        $this->authorize('update', $person);

        $this->applyFields($person, $request->validated());

        return PersonResource::make($person->fresh(['interests', 'fieldSources']));
    }

    public function destroy(Person $person): JsonResponse
    {
        $this->authorize('delete', $person);

        $person->delete();

        return response()->json(status: 204);
    }

    /**
     * Campurile cu provenienta trec prin WritePersonField ca OwnerManual —
     * deci devin protejate de sincronizarile ulterioare (docs/04 § 3).
     * Restul se scriu direct: nu sunt in conflict cu nicio alta sursa.
     */
    private function applyFields(Person $person, array $data): void
    {
        foreach (WritePersonField::TRACKED as $field) {
            if (array_key_exists($field, $data)) {
                ($this->writeField)($person, $field, $data[$field], FieldSource::OwnerManual);
            }
        }

        $direct = array_intersect_key($data, array_flip(['budget_min', 'budget_max', 'notes']));

        if ($direct !== []) {
            $person->update($direct);
        }

        if (isset($data['display_name'])) {
            $person->update(['given_name_normalized' => $this->firstName($data['display_name'])]);
        }
    }

    private function firstName(string $displayName): ?string
    {
        return $this->normalizer->firstName($displayName);
    }
}
