<?php

namespace App\Http\Api\V1\Controllers;

use App\Domain\Occasions\Models\Occasion;
use App\Domain\People\Enums\FieldSource;
use App\Http\Api\V1\Resources\OccasionResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class OccasionController extends Controller
{
    /** Ocaziile utilizatorului, cele mai apropiate primele. */
    public function index(Request $request): AnonymousResourceCollection
    {
        $occasions = Occasion::query()
            ->where('user_id', $request->user()->id)
            ->when($request->boolean('unconfirmed'), fn ($q) => $q
                ->whereNull('confirmed_at')->whereNull('rejected_at'))
            ->with(['person', 'nameDay', 'holiday'])
            ->get()
            ->sortBy(fn (Occasion $o) => $o->daysUntil())
            ->values();

        return OccasionResource::collection($occasions);
    }

    /**
     * Confirmă sau respinge o ocazie dedusă.
     *
     * „Nu sărbătorește” se păstrează ca respingere, nu ca ștergere: altfel
     * următoarea sincronizare ar re-propune aceeași onomastică.
     */
    public function update(Request $request, Occasion $occasion): OccasionResource
    {
        abort_unless($occasion->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'status'   => ['required', Rule::in(['confirmed', 'rejected'])],
            'month'    => ['nullable', 'integer', 'between:1,12'],
            'day'      => ['nullable', 'integer', 'between:1,31'],
            'is_muted' => ['nullable', 'boolean'],
        ]);

        $confirmed = $data['status'] === 'confirmed';

        $changes = [
            'confirmed_at' => $confirmed ? now() : null,
            'rejected_at'  => $confirmed ? null : now(),
        ];

        // Utilizatorul poate corecta data odată cu confirmarea: „da, are
        // onomastica, dar pe 6 mai” — caz frecvent între cele două calendare.
        if (isset($data['month'], $data['day'])) {
            $changes['month'] = $data['month'];
            $changes['day'] = $data['day'];
        }

        if (array_key_exists('is_muted', $data)) {
            $changes['is_muted'] = (bool) $data['is_muted'];
        }

        // O ocazie corectată de utilizator nu mai e o deducere.
        if ($confirmed) {
            $changes['source'] = FieldSource::OwnerManual;
            $changes['confidence'] = FieldSource::OwnerManual->defaultConfidence();
        }

        $occasion->update($changes);

        return OccasionResource::make($occasion->fresh(['person', 'nameDay', 'holiday']));
    }
}
