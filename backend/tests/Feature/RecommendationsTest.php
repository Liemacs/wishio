<?php

use App\Domain\Catalog\Models\Product;
use App\Domain\People\Models\GiftHistory;
use App\Domain\People\Models\Interest;
use App\Domain\People\Models\Person;
use App\Domain\People\Models\PersonAvoid;
use App\Domain\Recommendations\Actions\GenerateRecommendations;
use App\Domain\Recommendations\DTOs\GiftCriteria;
use App\Domain\Recommendations\Models\RecommendationRun;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\InterestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(InterestSeeder::class);
    $this->seed(CatalogSeeder::class);

    $this->user = User::factory()->create(['locale' => 'ro']);
    $this->generate = app(GenerateRecommendations::class);
});

function personWith(User $user, array $interestCodes, array $attributes = []): Person
{
    $person = Person::create(array_merge([
        'user_id' => $user->id, 'display_name' => 'Alex', 'gender' => 'm',
    ], $attributes));

    $ids = Interest::whereIn('code', $interestCodes)->pluck('id');

    $person->interests()->attach(
        $ids->mapWithKeys(fn ($id) => [$id => ['source' => 'owner_manual', 'confidence' => 0.9]])->all()
    );

    return $person->load('interests');
}

it('genereaza recomandari din interesele persoanei', function () {
    $person = personWith($this->user, ['audio', 'coffee_gear']);

    $run = ($this->generate)($person, 500, 6000);

    expect($run->status)->toBe('ready')->and($run->items)->not->toBeEmpty();

    // Fiecare produs sugerat trebuie să atingă cel puțin un interes al persoanei.
    foreach ($run->items as $item) {
        expect($item->product->interests->pluck('code')->intersect(['audio', 'coffee_gear']))
            ->not->toBeEmpty();
    }
});

it('recomanda doar produse care exista in catalog', function () {
    // Regula 2 din CLAUDE.md: AI-ul nu inventeaza niciodata un produs.
    $person = personWith($this->user, ['audio']);

    $run = ($this->generate)($person, null, null);

    foreach ($run->items as $item) {
        expect(Product::whereKey($item->product_id)->exists())->toBeTrue();
    }
});

it('nu recomanda produse care nu pot fi cadou', function () {
    $person = personWith($this->user, ['audio', 'computing']);

    $run = ($this->generate)($person, null, null);

    expect($run->items->pluck('product.is_giftable')->unique()->all())->toBe([true]);
});

it('respecta bugetul', function () {
    $person = personWith($this->user, ['audio', 'coffee_gear', 'fitness_gym']);

    $run = ($this->generate)($person, 500, 1500);

    foreach ($run->items as $item) {
        expect((float) $item->product->bestOffer()->price)->toBeGreaterThanOrEqual(500.0)
            ->and((float) $item->product->bestOffer()->price)->toBeLessThanOrEqual(1500.0);
    }
});

it('exclude ce a primit deja persoana', function () {
    $person = personWith($this->user, ['audio']);
    $sony   = Product::where('title', 'like', '%WH-1000XM5%')->sole();

    GiftHistory::create([
        'user_id' => $this->user->id, 'person_id' => $person->id,
        'product_id' => $sony->id, 'title' => $sony->title, 'year' => 2025,
    ]);

    $run = ($this->generate)($person->fresh(['interests', 'giftHistory']), null, null);

    expect($run->items->pluck('product_id'))->not->toContain($sony->id);
});

it('exclude categoriile marcate ca de evitat', function () {
    // Un singur cadou nepotrivit strica mai mult decat ajuta zece potrivite.
    $person = personWith($this->user, ['audio', 'fragrance_men']);

    PersonAvoid::create([
        'person_id'   => $person->id,
        'interest_id' => Interest::where('code', 'fragrance_men')->value('id'),
    ]);

    $run = ($this->generate)($person->fresh(['interests', 'avoids.interest']), null, null);

    expect($run->items->pluck('product.title')->implode(' '))->not->toContain('Sauvage');
});

it('diversifica rezultatele', function () {
    // Fara asta, opt perechi de casti pentru cineva care asculta muzica.
    $person = personWith($this->user, ['audio', 'coffee_gear', 'fitness_gym', 'board_games']);

    $run = ($this->generate)($person, null, null);

    $perCategory = $run->items->groupBy('product.product_category_id')->map->count();

    expect($perCategory->max())->toBeLessThanOrEqual(config('wishio.recommendations.max_per_category'));
});

it('ordoneaza dupa scor', function () {
    $person = personWith($this->user, ['audio', 'coffee_gear']);

    $scores = ($this->generate)($person, null, null)->items->pluck('score');

    expect($scores->all())->toBe($scores->sortDesc()->values()->all());
});

it('pastreaza descompunerea scorului, ca rezultatul sa fie depanabil', function () {
    $person = personWith($this->user, ['audio']);

    $item = ($this->generate)($person, 500, 6000)->items->first();

    expect($item->score_breakdown)
        ->toHaveKeys(['interest_overlap', 'budget_fit', 'gift_score', 'freshness', 'sponsored']);
});

it('salveaza criteriile folosite', function () {
    $person = personWith($this->user, ['audio']);

    $run = ($this->generate)($person, 500, 2000);

    expect($run->criteria['interests'])->toContain('audio')
        ->and($run->criteria['price_range'])->toBe(['min' => 500, 'max' => 2000]);
});

it('functioneaza fara consimtamant AI', function () {
    // Aplicatia trebuie sa mearga integral si pentru cine refuza — altfel
    // consimtamantul e o formalitate, nu o alegere (docs/06 § 2).
    $person = personWith($this->user, ['audio']);

    $run = ($this->generate)($person, null, null);

    expect($this->user->ai_consent_at)->toBeNull()
        ->and($run->provider)->toBe('rules')
        ->and($run->status)->toBe('ready')
        ->and($run->items)->not->toBeEmpty()
        ->and($run->items->pluck('reason')->filter())->toBeEmpty();
});

it('nu cade daca persoana nu are niciun interes', function () {
    $person = Person::create(['user_id' => $this->user->id, 'display_name' => 'Necunoscut']);

    $run = ($this->generate)($person, null, null);

    expect($run->status)->toBe('ready')
        // Mai bine niciun rezultat decat opt produse la intamplare.
        ->and($run->items)->toBeEmpty();
});

it('arunca codurile de interes inventate de AI', function () {
    // Nu le „reparam” si nu le aproximam: un cod inventat care seamana cu unul
    // real ar produce recomandari plauzibile si gresite.
    $criteria = GiftCriteria::fromAi([
        'interests'   => ['audio', 'inventat_de_ai', 'coffee_gear'],
        'confidence'  => 0.9,
        'price_range' => ['min' => 100, 'max' => 500],
    ]);

    expect($criteria->interests)->toBe(['audio', 'coffee_gear']);
});

it('porneste o rulare prin API si o intoarce cand e gata', function () {
    $person = personWith($this->user, ['audio']);

    $runId = $this->actingAs($this->user)
        ->postJson("/api/v1/people/{$person->id}/recommendations", ['budget_min' => 500, 'budget_max' => 6000])
        ->assertStatus(202)
        ->json('data.id');

    $this->actingAs($this->user)
        ->getJson("/api/v1/recommendations/$runId")
        ->assertOk()
        ->assertJsonPath('data.status', 'ready')
        ->assertJsonPath('data.has_ai', false)
        ->assertJsonStructure(['data' => ['items' => [['rank', 'score', 'product' => ['title', 'price']]]]]);
});

it('nu lasa un utilizator sa vada recomandarile altuia', function () {
    $other  = User::factory()->create();
    $person = personWith($other, ['audio']);
    $run    = RecommendationRun::create([
        'user_id' => $other->id, 'person_id' => $person->id,
        'kind' => 'gift', 'locale' => 'ro', 'status' => 'ready',
    ]);

    $this->actingAs($this->user)->getJson("/api/v1/recommendations/{$run->id}")->assertForbidden();
    $this->actingAs($this->user)->postJson("/api/v1/people/{$person->id}/recommendations")->assertForbidden();
});

it('inregistreaza consimtamantul AI', function () {
    $this->actingAs($this->user)->postJson('/api/v1/ai-consent', ['granted' => true])
        ->assertOk()->assertJsonPath('data.ai_consent', true);

    expect($this->user->fresh()->ai_consent_at)->not->toBeNull();

    $this->actingAs($this->user)->postJson('/api/v1/ai-consent', ['granted' => false])->assertOk();

    expect($this->user->fresh()->ai_consent_at)->toBeNull();
});
