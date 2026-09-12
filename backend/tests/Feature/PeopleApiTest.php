<?php

use App\Domain\People\Enums\FieldSource;
use App\Domain\People\Models\Interest;
use App\Domain\People\Models\Person;
use App\Models\User;
use Database\Seeders\InterestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['locale' => 'ro']);
});

it('cere autentificare pentru tot ce tine de persoane', function (string $method, string $uri) {
    $this->withHeader('Accept', 'application/json')
        ->json($method, $uri)
        ->assertUnauthorized();
})->with([
    ['GET', '/api/v1/people'],
    ['POST', '/api/v1/people'],
    ['GET', '/api/v1/interests'],
]);

it('creeaza o persoana', function () {
    $this->actingAs($this->user)
        ->postJson('/api/v1/people', [
            'display_name'     => 'Alex Ciobanu',
            'relationship'     => 'friend',
            'gender'           => 'm',
            'birth_date'       => '1998-04-23',
            'birth_year_known' => true,
            'budget_min'       => 500,
            'budget_max'       => 1500,
        ])
        ->assertCreated()
        ->assertJsonPath('data.display_name', 'Alex Ciobanu')
        ->assertJsonPath('data.age', fn ($age) => is_int($age));

    $person = Person::sole();

    expect($person->user_id)->toBe($this->user->id)
        // Prenumele normalizat alimenteaza name-day resolver-ul.
        ->and($person->given_name_normalized)->toBe('alex');
});

it('marcheaza ca manuale campurile scrise prin API', function () {
    // Tot ce vine de la utilizator prin API e owner_manual, deci protejat
    // fata de sincronizarile ulterioare (docs/04 § 3).
    $this->actingAs($this->user)
        ->postJson('/api/v1/people', ['display_name' => 'Ana', 'birth_date' => '1990-01-15']);

    $person = Person::sole();

    expect($person->trustLevel('birth_date'))->toBe(FieldSource::OwnerManual)
        ->and($person->isOverridden('birth_date'))->toBeTrue();
});

it('valideaza datele de intrare', function () {
    $this->actingAs($this->user)
        ->postJson('/api/v1/people', [
            'display_name' => '',
            'gender'       => 'x',
            'birth_date'   => now()->addYear()->toDateString(),
            'budget_min'   => 2000,
            'budget_max'   => 500,
        ])
        ->assertJsonValidationErrors(['display_name', 'gender', 'birth_date', 'budget_max']);
});

it('listeaza doar persoanele utilizatorului curent', function () {
    Person::create(['user_id' => $this->user->id, 'display_name' => 'A mea']);
    Person::create(['user_id' => User::factory()->create()->id, 'display_name' => 'A altcuiva']);

    $this->actingAs($this->user)
        ->getJson('/api/v1/people')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.display_name', 'A mea');
});

it('nu lasa un utilizator sa vada persoana altuia', function (string $method) {
    // Regula 3 din CLAUDE.md, verificata pe fiecare verb.
    $other = Person::create([
        'user_id'      => User::factory()->create()->id,
        'display_name' => 'Secret',
    ]);

    $this->actingAs($this->user)
        ->json($method, "/api/v1/people/{$other->id}", ['display_name' => 'Furat'])
        ->assertForbidden();

    expect($other->fresh()->display_name)->toBe('Secret');
})->with(['GET', 'PATCH', 'DELETE']);

it('actualizeaza o persoana', function () {
    $person = Person::create(['user_id' => $this->user->id, 'display_name' => 'Ana']);

    $this->actingAs($this->user)
        ->patchJson("/api/v1/people/{$person->id}", ['display_name' => 'Anișoara', 'notes' => 'Preferă cărți'])
        ->assertOk()
        ->assertJsonPath('data.display_name', 'Anișoara');

    expect($person->fresh()->notes)->toBe('Preferă cărți');
});

it('sterge o persoana', function () {
    $person = Person::create(['user_id' => $this->user->id, 'display_name' => 'Ana']);

    $this->actingAs($this->user)->deleteJson("/api/v1/people/{$person->id}")->assertNoContent();

    expect(Person::count())->toBe(0)
        ->and(Person::withTrashed()->count())->toBe(1);
});

it('livreaza taxonomia de interese in limba cererii', function () {
    $this->seed(InterestSeeder::class);

    $ro = $this->actingAs($this->user)->withHeader('Accept-Language', 'ro')->getJson('/api/v1/interests');
    $ru = $this->actingAs($this->user)->withHeader('Accept-Language', 'ru')->getJson('/api/v1/interests');

    $ro->assertOk()->assertJsonCount(15, 'data');

    expect($ro->json('data.0.label'))->toBe('Tehnologie')
        ->and($ru->json('data.0.label'))->toBe('Технологии');
});

it('salveaza interesele alese de utilizator', function () {
    $this->seed(InterestSeeder::class);
    $person = Person::create(['user_id' => $this->user->id, 'display_name' => 'Alex']);

    $this->actingAs($this->user)
        ->putJson("/api/v1/people/{$person->id}/interests", ['codes' => ['audio', 'motorsport']])
        ->assertOk()
        ->assertJsonCount(2, 'data.interests');

    expect($person->interests()->pluck('code')->sort()->values()->all())->toBe(['audio', 'motorsport']);
});

it('respinge un cod de interes care nu exista in taxonomie', function () {
    // Acelasi contract care protejeaza raspunsul AI — regula 2 din CLAUDE.md.
    $this->seed(InterestSeeder::class);
    $person = Person::create(['user_id' => $this->user->id, 'display_name' => 'Alex']);

    $this->actingAs($this->user)
        ->putJson("/api/v1/people/{$person->id}/interests", ['codes' => ['inventat_de_ai']])
        ->assertJsonValidationErrors('codes.0');
});

it('nu sterge interesele deduse cand utilizatorul isi salveaza propriile alegeri', function () {
    $this->seed(InterestSeeder::class);
    $person = Person::create(['user_id' => $this->user->id, 'display_name' => 'Alex']);

    $inferred = Interest::where('code', 'coffee')->sole();
    $person->interests()->attach($inferred->id, [
        'source'     => FieldSource::AiInferred->value,
        'confidence' => 0.6,
    ]);

    $this->actingAs($this->user)
        ->putJson("/api/v1/people/{$person->id}/interests", ['codes' => ['audio']]);

    expect($person->interests()->pluck('code')->sort()->values()->all())->toBe(['audio', 'coffee']);
});
