<?php

namespace Database\Seeders;

use App\Domain\Catalog\Actions\SyncCatalog;
use App\Domain\Catalog\Adapters\ManualCatalog;
use App\Domain\Catalog\Models\Merchant;
use App\Domain\Catalog\Models\Offer;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductCategory;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    private const MERCHANTS = [
        'darwin'  => 'Darwin',
        'bomba'   => 'Bomba',
        'enter'   => 'Enter',
        'maximum' => 'Maximum',
    ];

    public function run(): void
    {
        foreach (require database_path('seeders/data/product_categories.php') as $category) {
            ProductCategory::updateOrCreate(
                ['code' => $category['code']],
                [
                    'translations'    => ['ro' => $category['ro'], 'ru' => $category['ru'], 'en' => $category['en']],
                    'gift_base_score' => $category['score'],
                ]
            );
        }

        foreach (self::MERCHANTS as $code => $name) {
            Merchant::updateOrCreate(['code' => $code], ['name' => $name, 'country_code' => 'MD']);
        }

        $stats = app(SyncCatalog::class)(new ManualCatalog());

        $this->command?->info(sprintf(
            'Catalog: %d produse canonice din %d oferte · %d recomandabile · %d filtrate ca „nu e cadou”.',
            Product::count(),
            Offer::count(),
            Product::recommendable()->count(),
            Product::where('is_giftable', false)->count(),
        ));
    }
}
