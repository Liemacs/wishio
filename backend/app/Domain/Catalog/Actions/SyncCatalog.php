<?php

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Adapters\CatalogAdapter;
use App\Domain\Catalog\DTOs\CatalogItem;
use App\Domain\Catalog\Models\Merchant;
use App\Domain\Catalog\Models\Offer;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductCategory;
use App\Domain\People\Models\Interest;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Ingestia catalogului: normalizare, deduplicare, scor, mapare pe interese.
 *
 * Pipeline-ul din docs/05 § 4. Idempotent: se poate rula oricât.
 */
class SyncCatalog
{
    public function __construct(
        private readonly BuildCanonicalKey $canonicalKey,
        private readonly ScoreProduct $score,
    ) {}

    /** @return array{products: int, offers: int, filtered: int} */
    public function __invoke(CatalogAdapter $adapter, ?CarbonImmutable $since = null): array
    {
        $items = $adapter->fetch($since);
        $seenAt = now();
        $stats = ['products' => 0, 'offers' => 0, 'filtered' => 0];
        $touched = [];

        DB::transaction(function () use ($items, $seenAt, &$stats, &$touched) {
            foreach ($items as $item) {
                $product = $this->upsertProduct($item, $stats);
                $this->upsertOffer($item, $product, $seenAt, $stats);
                $touched[] = $product->id;
            }
        });

        $this->markStaleOffersOutOfStock($seenAt);

        return $stats;
    }

    private function upsertProduct(CatalogItem $item, array &$stats): Product
    {
        $key = ($this->canonicalKey)($item->title, $item->brand);
        $category = $item->categoryCode
            ? ProductCategory::where('code', $item->categoryCode)->first()
            : null;

        $product = Product::firstOrNew(['canonical_key' => $key]);

        if (! $product->exists) {
            $stats['products']++;
        }

        $product->fill([
            'title'               => $product->title ?: $item->title,
            'description'         => $product->description ?: $item->description,
            'brand'               => $product->brand ?: $item->brand,
            'product_category_id' => $product->product_category_id ?: $category?->id,
            'image_url'           => $product->image_url ?: $item->imageUrl,
        ])->save();

        $scored = ($this->score)($product->load('category'), $item->price);
        $product->update($scored);

        if (! $scored['is_giftable']) {
            $stats['filtered']++;
        }

        $this->syncInterests($product, $item);

        return $product;
    }

    private function upsertOffer(CatalogItem $item, Product $product, $seenAt, array &$stats): void
    {
        $merchant = Merchant::firstOrCreate(
            ['code' => $item->merchantCode],
            ['name' => ucfirst($item->merchantCode)]
        );

        $offer = Offer::updateOrCreate(
            ['merchant_id' => $merchant->id, 'external_id' => $item->externalId],
            [
                'product_id'   => $product->id,
                'price'        => $item->price,
                'old_price'    => $item->oldPrice,
                'currency'     => $item->currency,
                'deeplink'     => $item->deeplink,
                'in_stock'     => $item->inStock,
                'last_seen_at' => $seenAt,
            ]
        );

        if ($offer->wasRecentlyCreated) {
            $stats['offers']++;
        }
    }

    private function syncInterests(Product $product, CatalogItem $item): void
    {
        if ($item->interests === []) {
            return;
        }

        $ids = Interest::whereIn('code', $item->interests)->pluck('id');

        $product->interests()->syncWithoutDetaching(
            $ids->mapWithKeys(fn (int $id) => [$id => ['weight' => 1.0]])->all()
        );
    }

    /**
     * Ofertele nevăzute de mai multe zile ies din stoc, nu se șterg:
     * istoricul de cadouri și clickurile trebuie să rămână valide.
     */
    private function markStaleOffersOutOfStock($seenAt): void
    {
        Offer::where('last_seen_at', '<', $seenAt->copy()->subDays(config('wishio.catalog.stale_after_days')))
            ->where('in_stock', true)
            ->update(['in_stock' => false]);
    }
}
