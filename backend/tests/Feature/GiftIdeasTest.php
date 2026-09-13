<?php

use App\Domain\Catalog\Models\OutboundClick;
use App\Domain\Catalog\Models\Product;
use App\Domain\People\Models\GiftHistory;
use App\Domain\People\Models\GiftIdea;
use App\Domain\People\Models\Interest;
use App\Domain\People\Models\Person;
use App\Domain\Recommendations\Actions\GenerateRecommendations;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\InterestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(InterestSeeder::class);
    $this->seed(CatalogSeeder::class);

    $this->user = User::factory()->create([
        'email' => 'ana@example.com', 'password' => 'parola-buna', 'locale' => 'ro', 'timezone' => 'Europe/Chisinau',
    ]);
    $this->person = Person::create(['user_id' => $this->user->id, 'display_name' => 'Alex']);
    $this->product = Product::whereHas('offers', fn ($query) => $query->where('in_stock', true))->with('offers')->first();
});

it('salveaza o idee din catalog, cu titlul si pretul de acum', function () {
    $response = $this->actingAs($this->user)
        ->postJson("/api/v1/people/{$this->person->id}/ideas", ['product_id' => $this->product->id])
        ->assertCreated()
        ->assertJsonPath('data.title', $this->product->title)
        ->assertJsonPath('data.status', 'idea');

    expect((float) $response->json('data.price'))->toBe((float) $this->product->bestOffer()->price)
        ->and($response->json('data.offer_id'))->toBe($this->product->bestOffer()->id);
});

it('nu salveaza de doua ori acelasi produs pentru aceeasi persoana', function () {
    $this->actingAs($this->user)->postJson("/api/v1/people/{$this->person->id}/ideas", ['product_id' => $this->product->id])->assertCreated();
    $this->actingAs($this->user)->postJson("/api/v1/people/{$this->person->id}/ideas", ['product_id' => $this->product->id])->assertOk();

    expect(GiftIdea::count())->toBe(1);
});

it('lasa starea sa avanseze la o noua salvare, dar nu sa dea inapoi', function () {
    $this->actingAs($this->user)->postJson("/api/v1/people/{$this->person->id}/ideas", ['product_id' => $this->product->id, 'status' => 'purchased']);
    $this->actingAs($this->user)->postJson("/api/v1/people/{$this->person->id}/ideas", ['product_id' => $this->product->id]);

    expect(GiftIdea::sole()->status)->toBe('purchased');
});

it('salveaza o idee scrisa de mana', function () {
    $this->actingAs($this->user)
        ->postJson("/api/v1/people/{$this->person->id}/ideas", ['title' => '  O carte de bucate  '])
        ->assertCreated()
        ->assertJsonPath('data.title', 'O carte de bucate')
        ->assertJsonPath('data.product_id', null);

    $this->actingAs($this->user)
        ->postJson("/api/v1/people/{$this->person->id}/ideas", [])
        ->assertJsonValidationErrors('title');
});

it('schimba starea unei idei', function () {
    $idea = GiftIdea::create(['user_id' => $this->user->id, 'person_id' => $this->person->id, 'title' => 'Căști']);

    $this->actingAs($this->user)->patchJson("/api/v1/ideas/{$idea->id}", ['status' => 'chosen'])
        ->assertOk()
        ->assertJsonPath('data.status', 'chosen');

    // „Oferit” nu e o stare: e mutarea în istoric.
    $this->actingAs($this->user)->patchJson("/api/v1/ideas/{$idea->id}", ['status' => 'given'])
        ->assertJsonValidationErrors('status');
});

it('muta ideea in istoric cand cadoul a fost oferit, in anul din fusul utilizatorului', function () {
    // 1 ianuarie, 00:30 la Chișinău; în UTC e încă 31 decembrie.
    $this->travelTo(CarbonImmutable::parse('2026-12-31 22:30', 'UTC'));

    $idea = $this->actingAs($this->user)
        ->postJson("/api/v1/people/{$this->person->id}/ideas", ['product_id' => $this->product->id])
        ->json('data.id');

    $this->actingAs($this->user)->postJson("/api/v1/ideas/{$idea}/given")
        ->assertCreated()
        ->assertJsonPath('data.year', 2027);

    $entry = GiftHistory::sole();

    expect(GiftIdea::count())->toBe(0)
        ->and($entry->product_id)->toBe($this->product->id)
        ->and($entry->title)->toBe($this->product->title)
        ->and((float) $entry->amount)->toBe((float) $this->product->bestOffer()->price);
});

it('nu mai recomanda un produs deja oferit aceleiasi persoane', function () {
    $this->person->interests()->attach(
        Interest::whereIn('code', ['audio', 'coffee_gear'])->pluck('id')
            ->mapWithKeys(fn ($id) => [$id => ['source' => 'owner_manual', 'confidence' => 0.9]])->all()
    );

    $first = app(GenerateRecommendations::class)($this->person->fresh(), null, null);
    $productId = $first->items->first()->product_id;

    $idea = $this->actingAs($this->user)
        ->postJson("/api/v1/people/{$this->person->id}/ideas", ['product_id' => $productId])
        ->json('data.id');
    $this->actingAs($this->user)->postJson("/api/v1/ideas/{$idea}/given")->assertCreated();

    $second = app(GenerateRecommendations::class)($this->person->fresh(), null, null);

    expect($second->items->pluck('product_id'))->not->toContain($productId);
});

it('arata in rezultatele recomandarilor ce e deja salvat', function () {
    $this->person->interests()->attach(
        Interest::whereIn('code', ['audio', 'coffee_gear'])->pluck('id')
            ->mapWithKeys(fn ($id) => [$id => ['source' => 'owner_manual', 'confidence' => 0.9]])->all()
    );

    $run = $this->actingAs($this->user)->postJson("/api/v1/people/{$this->person->id}/recommendations", [])->json('data.id');
    $productId = $this->actingAs($this->user)->getJson("/api/v1/recommendations/{$run}")->json('data.items.0.product.id');

    $idea = $this->actingAs($this->user)
        ->postJson("/api/v1/people/{$this->person->id}/ideas", ['product_id' => $productId])
        ->json('data.id');

    $this->actingAs($this->user)->getJson("/api/v1/recommendations/{$run}")
        ->assertJsonPath('data.items.0.idea_id', $idea)
        ->assertJsonPath('data.items.1.idea_id', null);
});

it('completeaza istoricul si de mana, ordonat pe ani', function () {
    foreach ([['Parfum', 2024], ['Căști', 2026]] as [$title, $year]) {
        $this->actingAs($this->user)
            ->postJson("/api/v1/people/{$this->person->id}/history", ['title' => $title, 'year' => $year, 'amount' => 900])
            ->assertCreated();
    }

    $this->actingAs($this->user)->getJson("/api/v1/people/{$this->person->id}/gifts")
        ->assertOk()
        ->assertJsonPath('data.history.0.title', 'Căști')
        ->assertJsonPath('data.history.1.year', 2024);
});

it('sterge o idee si o intrare din istoric', function () {
    $idea = GiftIdea::create(['user_id' => $this->user->id, 'person_id' => $this->person->id, 'title' => 'Căști']);
    $entry = GiftHistory::create(['user_id' => $this->user->id, 'person_id' => $this->person->id, 'title' => 'Parfum', 'year' => 2025]);

    $this->actingAs($this->user)->deleteJson("/api/v1/ideas/{$idea->id}")->assertNoContent();
    $this->actingAs($this->user)->deleteJson("/api/v1/history/{$entry->id}")->assertNoContent();

    expect(GiftIdea::count())->toBe(0)->and(GiftHistory::count())->toBe(0);
});

it('nu lasa pe altcineva sa vada sau sa schimbe ideile si istoricul', function () {
    $idea = GiftIdea::create(['user_id' => $this->user->id, 'person_id' => $this->person->id, 'title' => 'Căști']);
    $entry = GiftHistory::create(['user_id' => $this->user->id, 'person_id' => $this->person->id, 'title' => 'Parfum', 'year' => 2025]);
    $stranger = User::factory()->create();

    $this->actingAs($stranger)->getJson("/api/v1/people/{$this->person->id}/gifts")->assertForbidden();
    $this->actingAs($stranger)->postJson("/api/v1/people/{$this->person->id}/ideas", ['title' => 'X'])->assertForbidden();
    $this->actingAs($stranger)->postJson("/api/v1/people/{$this->person->id}/history", ['title' => 'X', 'year' => 2025])->assertForbidden();
    $this->actingAs($stranger)->patchJson("/api/v1/ideas/{$idea->id}", ['status' => 'chosen'])->assertForbidden();
    $this->actingAs($stranger)->postJson("/api/v1/ideas/{$idea->id}/given")->assertForbidden();
    $this->actingAs($stranger)->deleteJson("/api/v1/ideas/{$idea->id}")->assertForbidden();
    $this->actingAs($stranger)->deleteJson("/api/v1/history/{$entry->id}")->assertForbidden();

    $this->actingAs($stranger)->getJson('/api/v1/ideas')->assertOk()->assertJsonCount(0, 'data');

    expect(GiftIdea::count())->toBe(1)->and(GiftHistory::count())->toBe(1);
});

it('arata toate ideile, fara cele ale persoanelor sterse', function () {
    $other = Person::create(['user_id' => $this->user->id, 'display_name' => 'Maria']);
    GiftIdea::create(['user_id' => $this->user->id, 'person_id' => $this->person->id, 'title' => 'Căști']);
    GiftIdea::create(['user_id' => $this->user->id, 'person_id' => $other->id, 'title' => 'Eșarfă']);

    $other->delete();

    $this->actingAs($this->user)->getJson('/api/v1/ideas')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.person.display_name', 'Alex');
});

it('include ideile in exportul de date', function () {
    GiftIdea::create(['user_id' => $this->user->id, 'person_id' => $this->person->id, 'title' => 'Căști', 'status' => 'chosen']);

    $this->actingAs($this->user)->getJson('/api/v1/account/export')
        ->assertJsonPath('people.0.gift_ideas.0.title', 'Căști')
        ->assertJsonPath('people.0.gift_ideas.0.status', 'chosen');
});

it('sterge ideile odata cu contul', function () {
    GiftIdea::create(['user_id' => $this->user->id, 'person_id' => $this->person->id, 'title' => 'Căști']);

    $this->actingAs($this->user)
        ->deleteJson('/api/v1/account', ['confirm_email' => 'ana@example.com', 'password' => 'parola-buna'])
        ->assertNoContent();

    expect(GiftIdea::count())->toBe(0);
});

it('inregistreaza magazinul deschis dintr-o idee salvata, separat de recomandari', function () {
    $offer = $this->product->bestOffer();

    $this->actingAs($this->user)
        ->postJson("/api/v1/offers/{$offer->id}/click", ['person_id' => $this->person->id, 'context' => 'idea'])
        ->assertOk();

    expect(OutboundClick::sole()->context)->toBe('idea');
});
