<?php

namespace App\Domain\Catalog\Adapters;

use App\Domain\Catalog\DTOs\CatalogItem;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Catalog dintr-un fișier local.
 *
 * Sursa implicită până la apariția API-ului Magaziner (docs/00 § D-016).
 * Rămâne utilă și după: în teste vrei un catalog mic și previzibil.
 */
class ManualCatalog implements CatalogAdapter
{
    public function __construct(private readonly ?string $path = null) {}

    public function fetch(?CarbonImmutable $since = null): Collection
    {
        $path = $this->path ?? database_path('seeders/data/catalog.php');

        if (! file_exists($path)) {
            return collect();
        }

        return collect(require $path)->map(fn (array $row) => new CatalogItem(
            merchantCode: $row['merchant'],
            externalId: $row['external_id'],
            title: $row['title'],
            price: (float) $row['price'],
            deeplink: $row['deeplink'],
            brand: $row['brand'] ?? null,
            description: $row['description'] ?? null,
            categoryCode: $row['category'] ?? null,
            imageUrl: $row['image'] ?? null,
            oldPrice: isset($row['old_price']) ? (float) $row['old_price'] : null,
            inStock: $row['in_stock'] ?? true,
            interests: $row['interests'] ?? [],
        ));
    }

    public function supports(string $countryCode): bool
    {
        return true;
    }
}
