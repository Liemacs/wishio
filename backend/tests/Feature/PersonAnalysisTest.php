<?php

use App\Domain\People\Models\Person;
use App\Domain\Recommendations\Actions\GenerateRecommendations;
use App\Models\User;
use App\Support\Ai\AiBudget;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\InterestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(InterestSeeder::class);
    $this->user = User::factory()->create(['locale' => 'ro']);
    $this->person = Person::create(['user_id' => $this->user->id, 'display_name' => 'Alex']);
});

it('propune interese dintr-o fraza libera', function () {
    $response = $this->actingAs($this->user)
        ->postJson("/api/v1/people/{$this->person->id}/analyze", [
            'text' => 'Alex lucrează ca programator, îi plac mașinile și merge la sală',
        ])
        ->assertOk();

    $codes = collect($response->json('data.suggestions'))->pluck('interest.code');

    expect($codes)->toContain('computing', 'car_accessories', 'fitness_gym');
});

it('functioneaza si in rusa', function () {
    $response = $this->actingAs($this->user)
        ->postJson("/api/v1/people/{$this->person->id}/analyze", [
            'text' => 'Ему нравится рыбалка и хорошее вино',
        ])
        ->assertOk();

    expect(collect($response->json('data.suggestions'))->pluck('interest.code'))
        ->toContain('fishing', 'wine');
});

it('doar propune, nu aplica', function () {
    // O deducere aplicata automat ar umple profilul cu presupuneri
    // pe care nimeni nu le-a verificat.
    $this->actingAs($this->user)
        ->postJson("/api/v1/people/{$this->person->id}/analyze", ['text' => 'îi plac mașinile']);

    expect($this->person->fresh()->interests)->toBeEmpty();
});

it('nu stocheaza textul liber', function () {
    // Poate contine date sensibile; noua ne trebuie doar rezultatul.
    $this->actingAs($this->user)
        ->postJson("/api/v1/people/{$this->person->id}/analyze", [
            'text' => 'Are diabet, fără dulciuri. Îi place muzica.',
        ])->assertOk();

    expect($this->person->fresh()->notes)->toBeNull();
});

it('nu lasa un utilizator sa analizeze persoana altuia', function () {
    $other = Person::create([
        'user_id' => User::factory()->create()->id, 'display_name' => 'Secret',
    ]);

    $this->actingAs($this->user)
        ->postJson("/api/v1/people/{$other->id}/analyze", ['text' => 'ceva'])
        ->assertForbidden();
});

it('cere un text destul de lung ca sa fie util', function () {
    $this->actingAs($this->user)
        ->postJson("/api/v1/people/{$this->person->id}/analyze", ['text' => 'x'])
        ->assertJsonValidationErrors('text');
});

it('trece pe ruta fara AI cand bugetul zilei e epuizat', function () {
    // Degradare, nu cadere: produsul continua sa functioneze (docs/11 § 3).
    $this->seed(CatalogSeeder::class);

    $this->user->update(['ai_consent_at' => now()]);

    $budget = app(AiBudget::class);
    $budget->record(config('wishio.ai.max_cost_per_day') + 1);

    expect($budget->hasRoom())->toBeFalse();

    $run = app(GenerateRecommendations::class)($this->person, null, null);

    expect($run->provider)->toBe('rules')
        ->and($run->status)->toBe('ready');
});

it('are loc in buget la inceputul zilei', function () {
    expect(app(AiBudget::class)->hasRoom())->toBeTrue()
        ->and(app(AiBudget::class)->spentToday())->toBe(0.0);
});
