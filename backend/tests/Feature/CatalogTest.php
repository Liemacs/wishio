<?php

use App\Domain\Catalog\Actions\BuildCanonicalKey;
use App\Domain\Catalog\Actions\SyncCatalog;
use App\Domain\Catalog\Adapters\ManualCatalog;
use App\Domain\Catalog\Models\OutboundClick;
use App\Domain\Catalog\Models\Product;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\InterestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(InterestSeeder::class);
    $this->seed(CatalogSeeder::class);
    $this->user = User::factory()->create();
});

it('uneste acelasi produs vandut la mai multe magazine', function () {
    // Fara asta, o lista de 8 sugestii ar contine de trei ori aceleasi casti.
    $sony = Product::where('title', 'like', '%WH-1000XM5%')->sole();

    expect($sony->offers)->toHaveCount(3)
        ->and($sony->offers->pluck('merchant_id')->unique())->toHaveCount(3);
});

it('arata cel mai bun pret dintre oferte', function () {
    $sony = Product::where('title', 'like', '%WH-1000XM5%')->with('offers.merchant')->sole();

    expect((float) $sony->bestOffer()->price)->toBe(5299.0)
        ->and($sony->bestOffer()->merchant->name)->toBe('Bomba');
});

it('construieste aceeasi cheie pentru titluri diferite ale aceluiasi produs', function (string $a, string $b, ?string $brand) {
    $key = app(BuildCanonicalKey::class);

    expect($key($a, $brand))->toBe($key($b, $brand));
})->with([
    ['Sony WH-1000XM5 Black', 'Căști wireless Sony WH-1000XM5, negru', 'Sony'],
    ['Mouse Logitech MX Master 3S', 'Logitech MX Master 3S wireless', 'Logitech'],
    ['Samsung Galaxy A54 128GB', 'Galaxy A54 Samsung 128 GB', 'Samsung'],
]);

it('nu uneste produse diferite care impart doar o cifra', function () {
    // „Dior Sauvage 100ml” si „Dior J'adore 100ml” sunt parfumuri diferite.
    // De aceea codul de model trebuie sa aiba SI litere, SI cifre.
    $key = app(BuildCanonicalKey::class);

    expect($key('Dior Sauvage 100ml', 'Dior'))->not->toBe($key("Dior J'adore 100ml", 'Dior'));
});

it('pastreaza cifrele din codurile de model', function () {
    // NameNormalizer elimina cifrele — corect pentru prenume, fatal aici.
    // „WH-1000XM5” ar deveni „whxm” si nimic nu s-ar mai uni.
    expect(app(BuildCanonicalKey::class)('Sony WH-1000XM5', 'Sony'))->toContain('1000xm5');
});

it('scoate din recomandari ce nu poate fi cadou', function (string $title) {
    $product = Product::where('title', 'like', "%$title%")->sole();

    expect($product->is_giftable)->toBeFalse()
        ->and($product->gift_score)->toBe(1);
})->with(['Cablu USB-C', 'Filtru de ulei', 'Cartuș toner', 'Sac de aspirator']);

it('nu include produsele nerecomandabile in cautare', function () {
    $titles = Product::recommendable()->pluck('title');

    expect($titles)->not->toContain('Cablu USB-C 2m Baseus')
        ->and($titles->count())->toBeGreaterThan(30);
});

it('da scor mai mare produselor evident de cadou', function () {
    $perfume = Product::where('title', 'like', '%Chanel%')->sole();
    $tool = Product::where('title', 'like', '%aspirator auto%')->first();

    expect($perfume->gift_score)->toBe(5)
        ->and($perfume->gift_score)->toBeGreaterThan($tool?->gift_score ?? 5);
});

it('nu rescrie scorurile puse manual', function () {
    // Stratul 2 din docs/05 § 4 bate stratul 1: corectia umana ramane.
    $product = Product::recommendable()->first();
    $product->update(['gift_score' => 5, 'score_is_manual' => true]);

    app(SyncCatalog::class)(new ManualCatalog);

    expect($product->fresh()->gift_score)->toBe(5);
});

it('nu creeaza duplicate la sincronizari repetate', function () {
    $before = Product::count();

    app(SyncCatalog::class)(new ManualCatalog);
    app(SyncCatalog::class)(new ManualCatalog);

    expect(Product::count())->toBe($before);
});

it('cauta produse dupa text', function () {
    $this->actingAs($this->user)
        ->getJson('/api/v1/products?q=Sony')
        ->assertOk()
        ->assertJsonPath('data.0.brand', 'Sony');
});

it('filtreaza dupa buget', function () {
    $prices = collect($this->actingAs($this->user)
        ->getJson('/api/v1/products?budget_min=500&budget_max=1500')
        ->assertOk()
        ->json('data'))->pluck('price');

    expect($prices)->not->toBeEmpty()
        ->and($prices->every(fn ($price) => $price >= 500 && $price <= 1500))->toBeTrue();
});

it('filtreaza dupa interese', function () {
    $data = $this->actingAs($this->user)
        ->getJson('/api/v1/products?interests[]=coffee_gear')
        ->assertOk()
        ->json('data');

    expect(collect($data)->pluck('title')->implode(' '))->toContain('Espressor');
});

it('respinge un cod de interes inexistent', function () {
    $this->actingAs($this->user)
        ->getJson('/api/v1/products?interests[]=inventat')
        ->assertJsonValidationErrors('interests.0');
});

it('spune cate alte magazine mai au produsul', function () {
    $sony = Product::where('title', 'like', '%WH-1000XM5%')->sole();

    $this->actingAs($this->user)
        ->getJson("/api/v1/products/{$sony->id}")
        ->assertOk()
        ->assertJsonPath('data.other_offers_count', 2);
});

it('inregistreaza clickul spre magazin si intoarce linkul', function () {
    $offer = Product::recommendable()->with('offers')->first()->offers->first();

    $this->actingAs($this->user)
        ->postJson("/api/v1/offers/{$offer->id}/click", ['context' => 'recommendation'])
        ->assertOk()
        ->assertJsonPath('data.url', $offer->deeplink);

    $click = OutboundClick::sole();

    expect($click->user_id)->toBe($this->user->id)
        ->and($click->merchant_id)->toBe($offer->merchant_id)
        ->and((float) $click->price)->toBe((float) $offer->price)
        ->and($click->context)->toBe('recommendation');
});

it('cere autentificare pentru catalog', function () {
    $this->withHeader('Accept', 'application/json')
        ->getJson('/api/v1/products')
        ->assertUnauthorized();
});
