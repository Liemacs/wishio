<?php

use App\Domain\Occasions\Models\Occasion;
use App\Domain\People\Actions\WritePersonField;
use App\Domain\People\Enums\FieldSource;
use App\Domain\People\Models\GiftHistory;
use App\Domain\People\Models\Person;
use App\Domain\Profiles\Models\PublicProfile;
use App\Domain\Reminders\Models\DeviceToken;
use App\Domain\Reminders\Models\UserSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'email' => 'ana@example.com', 'password' => 'parola-buna', 'locale' => 'ro',
    ]);

    $this->person = Person::create([
        'user_id'      => $this->user->id, 'display_name' => 'Alex',
        'relationship' => 'friend', 'notes' => 'Preferă cărți',
    ]);

    // Prin acțiune, nu direct: altfel nu se înregistrează proveniența.
    app(WritePersonField::class)($this->person, 'display_name', 'Alex', FieldSource::OwnerManual);

    Occasion::create([
        'user_id' => $this->user->id, 'person_id' => $this->person->id, 'type' => 'birthday',
        'month'   => 4, 'day' => 23, 'source' => 'owner_manual', 'confirmed_at' => now(),
    ]);

    GiftHistory::create([
        'user_id' => $this->user->id, 'person_id' => $this->person->id,
        'title'   => 'Căști', 'year' => 2025,
    ]);

    UserSettings::create(['user_id' => $this->user->id]);
    DeviceToken::create(['user_id' => $this->user->id, 'token' => 'tok-1', 'platform' => 'ios']);
});

it('exporta toate datele utilizatorului', function () {
    $data = $this->actingAs($this->user)->getJson('/api/v1/account/export')->assertOk()->json();

    expect($data['account']['email'])->toBe('ana@example.com')
        ->and($data['people'])->toHaveCount(1)
        ->and($data['people'][0]['name'])->toBe('Alex')
        // Notele sunt datele lui, chiar dacă le ținem criptate.
        ->and($data['people'][0]['notes'])->toBe('Preferă cărți')
        ->and($data['people'][0]['occasions'])->toHaveCount(1)
        ->and($data['people'][0]['gift_history'])->toHaveCount(1)
        ->and($data['settings']['push_enabled'])->toBeTrue();
});

it('exportul se descarca, nu se afiseaza', function () {
    $this->actingAs($this->user)->get('/api/v1/account/export')
        ->assertHeader('Content-Disposition', 'attachment; filename="wishio-export.json"');
});

it('exportul spune de unde avem fiecare informatie', function () {
    // Utilizatorul are dreptul sa stie proveniența datelor, nu doar valorile.
    $data = $this->actingAs($this->user)->getJson('/api/v1/account/export')->json();

    expect($data['people'][0]['field_sources'])->toHaveKey('display_name');
});

it('nu exporta datele altui utilizator', function () {
    Person::create(['user_id' => User::factory()->create()->id, 'display_name' => 'Secret']);

    $data = $this->actingAs($this->user)->getJson('/api/v1/account/export')->json();

    expect(collect($data['people'])->pluck('name'))->not->toContain('Secret');
});

it('sterge contul definitiv, cu tot ce tine de el', function () {
    $this->actingAs($this->user)
        ->deleteJson('/api/v1/account', ['confirm_email' => 'ana@example.com', 'password' => 'parola-buna'])
        ->assertNoContent();

    // Definitiv, nu dezactivat: un cont „sters” care ramane in baza nu e sters.
    // User nu are soft-delete: `forceDelete` e o ștergere reală.
    expect(User::count())->toBe(0)
        ->and(Person::withTrashed()->count())->toBe(0)
        ->and(Occasion::count())->toBe(0)
        ->and(GiftHistory::count())->toBe(0)
        ->and(UserSettings::count())->toBe(0)
        ->and(DeviceToken::count())->toBe(0);
});

it('sterge si profilul public cu tot cu completari', function () {
    PublicProfile::create([
        'user_id'      => $this->user->id, 'slug' => 'ana-test99',
        'display_name' => 'Ana', 'locale' => 'ro',
    ]);

    $this->actingAs($this->user)
        ->deleteJson('/api/v1/account', ['confirm_email' => 'ana@example.com', 'password' => 'parola-buna']);

    expect(PublicProfile::count())->toBe(0);
});

it('cere confirmarea emailului scris de mana', function () {
    // O stergere ireversibila nu trebuie declansata dintr-o apasare gresita.
    $this->actingAs($this->user)
        ->deleteJson('/api/v1/account', ['confirm_email' => 'altcineva@example.com', 'password' => 'parola-buna'])
        ->assertJsonValidationErrors('confirm_email');

    expect(User::count())->toBe(1);
});

it('cere parola', function () {
    // Altfel un telefon deblocat si lasat pe masa ar fi de ajuns.
    $this->actingAs($this->user)
        ->deleteJson('/api/v1/account', ['confirm_email' => 'ana@example.com', 'password' => 'gresita'])
        ->assertJsonValidationErrors('password');

    expect(User::count())->toBe(1);
});

it('nu lasa stergerea fara autentificare', function () {
    $this->withHeader('Accept', 'application/json')
        ->deleteJson('/api/v1/account', ['confirm_email' => 'ana@example.com'])
        ->assertUnauthorized();
});

it('afiseaza documentele legale in toate cele trei limbi', function (string $locale, string $expected) {
    $this->withHeader('Accept-Language', $locale)
        ->get('/legal/privacy')
        ->assertOk()
        ->assertSee($expected, escape: false);
})->with([
    ['ro', 'Politica de confidențialitate'],
    ['ru', 'Политика конфиденциальности'],
    ['en', 'Privacy Policy'],
]);

it('afiseaza termenii', function () {
    $this->withHeader('Accept-Language', 'ro')->get('/legal/terms')
        ->assertOk()
        ->assertSee('Termeni și condiții', escape: false);
});

it('respinge un document inexistent', function () {
    $this->get('/legal/inventat')->assertNotFound();
});

it('spune explicit ce NU colectam', function () {
    // Partea asta conteaza mai mult decat lista de ce colectam.
    $this->withHeader('Accept-Language', 'ro')->get('/legal/privacy')
        ->assertSee('Nu citim și nu stocăm numere de telefon', escape: false)
        ->assertSee('Nu urcăm agenda ta pe server', escape: false);
});
