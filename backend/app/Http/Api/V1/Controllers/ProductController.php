<?php

namespace App\Http\Api\V1\Controllers;

use App\Domain\Catalog\Models\Offer;
use App\Domain\Catalog\Models\OutboundClick;
use App\Domain\Catalog\Models\Product;
use App\Domain\People\Models\Interest;
use App\Http\Api\V1\Resources\ProductResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'q'           => ['nullable', 'string', 'max:120'],
            'interests'   => ['nullable', 'array', 'max:10'],
            'interests.*' => ['string', Rule::in(Interest::validCodes())],
            'budget_min'  => ['nullable', 'integer', 'min:0'],
            'budget_max'  => ['nullable', 'integer', 'min:0'],
            'limit'       => ['nullable', 'integer', 'between:1,50'],
        ]);

        $products = Product::query()
            ->recommendable()
            ->when($filters['q'] ?? null, fn ($query, $term) => $query
                ->where(fn ($q) => $q
                    ->where('title', 'like', "%$term%")
                    ->orWhere('brand', 'like', "%$term%")))
            ->when($filters['interests'] ?? null, fn ($query, $codes) => $query
                ->whereHas('interests', fn ($q) => $q->whereIn('code', $codes)))
            ->inBudget($filters['budget_min'] ?? null, $filters['budget_max'] ?? null)
            ->with(['offers.merchant', 'category', 'interests'])
            // Produsele mai „de cadou” primele; la scor egal, cele mai ieftine.
            ->orderByDesc('gift_score')
            ->limit($filters['limit'] ?? 20)
            ->get();

        return ProductResource::collection($products);
    }

    public function show(Product $product): ProductResource
    {
        return ProductResource::make($product->load(['offers.merchant', 'category', 'interests']));
    }

    /**
     * Clickul spre magazin. Evenimentul care aduce bani (docs/07 § 3).
     *
     * Îl înregistrăm noi, ca să avem cifra proprie de arătat comerciantului
     * la negociere — fără ea, discuția din docs/11 § 5 nu are pe ce sta.
     */
    public function click(Request $request, Offer $offer): JsonResponse
    {
        $data = $request->validate([
            'person_id' => ['nullable', 'integer', 'exists:people,id'],
            'context'   => ['nullable', Rule::in(['search', 'recommendation', 'wishlist'])],
        ]);

        OutboundClick::create([
            'user_id'     => $request->user()->id,
            'offer_id'    => $offer->id,
            'merchant_id' => $offer->merchant_id,
            'person_id'   => $data['person_id'] ?? null,
            'price'       => $offer->price,
            'context'     => $data['context'] ?? null,
        ]);

        return response()->json(['data' => ['url' => $offer->deeplink]]);
    }
}
