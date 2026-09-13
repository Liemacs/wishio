<?php

namespace App\Http\Api\V1\Controllers;

use App\Domain\Catalog\Models\Product;
use App\Domain\People\Actions\MarkGiftGiven;
use App\Domain\People\Actions\SaveGiftIdea;
use App\Domain\People\Models\GiftHistory;
use App\Domain\People\Models\GiftIdea;
use App\Domain\People\Models\Person;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Ideile de cadou și istoricul: S10, ecranele G1–G3 din docs/09.
 *
 * Istoricul e mereu privat (docs/04 § 5) și nu iese din contul proprietarului.
 */
class GiftController extends Controller
{
    private const OCCASION_TYPES = ['birthday', 'name_day', 'anniversary', 'holiday', 'custom'];

    public function index(Person $person): JsonResponse
    {
        $this->authorize('view', $person);

        return response()->json(['data' => [
            'ideas' => $person->giftIdeas()->with('product.offers')->latest('id')->get()
                ->map(fn (GiftIdea $idea) => $this->idea($idea))->values(),
            'history' => $person->giftHistory()->orderByDesc('year')->latest('id')->get()
                ->map(fn (GiftHistory $entry) => $this->entry($entry))->values(),
        ]]);
    }

    /** Toate ideile, pentru toate persoanele: varianta globală a ecranului G1. */
    public function ideas(Request $request): JsonResponse
    {
        $ideas = GiftIdea::where('user_id', $request->user()->id)
            // O persoană ștearsă din listă își ia ideile cu ea.
            ->whereHas('person')
            ->with(['product.offers', 'person'])
            ->latest('id')
            ->get();

        return response()->json(['data' => $ideas->map(fn (GiftIdea $idea) => $this->idea($idea) + [
            'person' => ['id' => $idea->person->id, 'display_name' => $idea->person->display_name],
        ])->values()]);
    }

    public function storeIdea(Request $request, Person $person, SaveGiftIdea $save): JsonResponse
    {
        $this->authorize('update', $person);

        $data = $request->validate([
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'title'      => ['required_without:product_id', 'nullable', 'string', 'max:190'],
            'status'     => ['sometimes', Rule::in(GiftIdea::STATUSES)],
        ]);

        $idea = $save(
            $person,
            isset($data['product_id']) ? Product::find($data['product_id']) : null,
            isset($data['title']) ? trim($data['title']) : null,
            $data['status'] ?? 'idea',
        );

        return response()->json(
            ['data' => $this->idea($idea->load('product.offers'))],
            $idea->wasRecentlyCreated ? 201 : 200,
        );
    }

    public function updateIdea(Request $request, GiftIdea $idea): JsonResponse
    {
        $this->authorize('update', $idea);

        $idea->update($request->validate([
            'status' => ['required', Rule::in(GiftIdea::STATUSES)],
        ]));

        return response()->json(['data' => $this->idea($idea->load('product.offers'))]);
    }

    public function destroyIdea(GiftIdea $idea): JsonResponse
    {
        $this->authorize('delete', $idea);

        $idea->delete();

        return response()->json(status: 204);
    }

    public function given(Request $request, GiftIdea $idea, MarkGiftGiven $markGiven): JsonResponse
    {
        $this->authorize('update', $idea);

        $data = $request->validate($this->historyRules(manual: false));

        $entry = $markGiven(
            $idea,
            $data['year'] ?? null,
            $data['occasion_type'] ?? null,
            isset($data['amount']) ? (float) $data['amount'] : null,
        );

        return response()->json(['data' => $this->entry($entry)], 201);
    }

    /** Un cadou oferit înainte de aplicație: istoricul e util din prima zi. */
    public function storeHistory(Request $request, Person $person): JsonResponse
    {
        $this->authorize('update', $person);

        $data = $request->validate($this->historyRules(manual: true));

        $entry = $person->giftHistory()->create([
            'user_id'       => $person->user_id,
            'title'         => trim($data['title']),
            'year'          => $data['year'],
            'occasion_type' => $data['occasion_type'] ?? null,
            'amount'        => $data['amount'] ?? null,
        ]);

        return response()->json(['data' => $this->entry($entry)], 201);
    }

    public function destroyHistory(GiftHistory $entry): JsonResponse
    {
        $this->authorize('delete', $entry);

        $entry->delete();

        return response()->json(status: 204);
    }

    /** @return array<string, list<mixed>> */
    private function historyRules(bool $manual): array
    {
        $rules = [
            'year'          => [$manual ? 'required' : 'nullable', 'integer', 'between:1900,'.(now()->year + 1)],
            'occasion_type' => ['nullable', Rule::in(self::OCCASION_TYPES)],
            'amount'        => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        ];

        if ($manual) {
            $rules['title'] = ['required', 'string', 'max:190'];
        }

        return $rules;
    }

    /** @return array<string, mixed> */
    private function idea(GiftIdea $idea): array
    {
        $offer = $idea->product?->bestOffer();

        return [
            'id'         => $idea->id,
            'person_id'  => $idea->person_id,
            'title'      => $idea->title,
            'status'     => $idea->status,
            'price'      => $idea->price !== null ? (float) $idea->price : null,
            'currency'   => $idea->currency,
            'product_id' => $idea->product_id,
            'image_url'  => $idea->product?->image_url,
            // Oferta de acum, nu cea de la salvare: prețul și stocul se schimbă.
            'offer_id' => $offer?->id,
        ];
    }

    /** @return array<string, mixed> */
    private function entry(GiftHistory $entry): array
    {
        return [
            'id'            => $entry->id,
            'title'         => $entry->title,
            'year'          => $entry->year,
            'occasion_type' => $entry->occasion_type,
            'amount'        => $entry->amount !== null ? (float) $entry->amount : null,
            'product_id'    => $entry->product_id,
        ];
    }
}
