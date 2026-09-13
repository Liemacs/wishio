<?php

use App\Domain\Catalog\Models\Offer;
use App\Domain\Catalog\Models\OutboundClick;
use App\Domain\Occasions\Models\Occasion;
use App\Domain\People\Actions\WritePersonField;
use App\Domain\People\Enums\FieldSource;
use App\Domain\People\Models\GiftHistory;
use App\Domain\People\Models\Person;
use App\Domain\People\Models\PersonAvoid;
use App\Domain\Profiles\Models\PublicProfile;
use App\Domain\Recommendations\Models\RecommendationRun;
use App\Domain\Reminders\Models\DeviceToken;
use App\Domain\Reminders\Models\UserSettings;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\InterestSeeder;
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

it('traduce erorile de confirmare in toate cele trei limbi', function (string $locale, string $password, string $email) {
    // Un ecran nu afiseaza niciodata o cheie bruta (CLAUDE.md, regula 1).
    // Pana acum, RO si RU primeau textul `auth.password` la parola gresita.
    $this->actingAs($this->user)->withHeader('Accept-Language', $locale)
        ->deleteJson('/api/v1/account', ['confirm_email' => 'ana@example.com', 'password' => 'gresita'])
        ->assertJsonValidationErrors(['password' => $password]);

    $this->actingAs($this->user)->withHeader('Accept-Language', $locale)
        ->deleteJson('/api/v1/account', ['confirm_email' => 'altcineva@example.com', 'password' => 'parola-buna'])
        ->assertJsonValidationErrors(['confirm_email' => $email]);
})->with([
    ['ro', 'Parola nu este corectă.', 'Emailul nu corespunde.'],
    ['ru', 'Неверный пароль.', 'Email не совпадает.'],
    ['en', 'The password is incorrect.', 'The email does not match.'],
]);

it('deschide documentele legale in limba aplicatiei, nu a telefonului', function () {
    // Browserul din aplicatie trimite limba telefonului. Utilizatorul poate
    // folosi aplicatia in rusa pe un telefon setat in engleza.
    $this->withHeader('Accept-Language', 'en')
        ->get('/legal/privacy?lang=ru')
        ->assertOk()
        ->assertSee('Политика конфиденциальности', escape: false);
});

it('ignora o limba nesuportata in URL', function () {
    $this->withHeader('Accept-Language', 'ro')
        ->get('/legal/privacy?lang=de')
        ->assertOk()
        ->assertSee('Politica de confidențialitate', escape: false);
});

it('lasa comutatorul de limba din pagina sa castige fata de URL', function () {
    // Altfel, un document deschis din aplicatie n-ar mai putea fi citit in alta limba.
    $this->withSession(['locale' => 'en'])
        ->get('/legal/privacy?lang=ru')
        ->assertSee('Privacy Policy', escape: false);
});

it('pastreaza limba in linkurile dintre documente', function () {
    $this->get('/legal/privacy?lang=ru')
        ->assertSee(route('legal', ['key' => 'terms', 'lang' => 'ru']), escape: false);
});

it('exporta si ce nu se vede in fisa persoanei', function () {
    // „Poți descărca toate datele tale”, din politică, trebuie să fie adevărat:
    // și clickurile, recomandările, dispozitivele, persoanele șterse încă păstrate.
    $this->seed(InterestSeeder::class);
    $this->seed(CatalogSeeder::class);

    $this->user->update(['ai_consent_at' => now(), 'ai_consent_asked_at' => now()]);
    PersonAvoid::create(['person_id' => $this->person->id, 'free_text' => 'alergic la nuci']);
    Person::create(['user_id' => $this->user->id, 'display_name' => 'Fost coleg'])->delete();

    $offer = Offer::with('product')->first();
    OutboundClick::create([
        'user_id'   => $this->user->id, 'offer_id' => $offer->id, 'merchant_id' => $offer->merchant_id,
        'person_id' => $this->person->id, 'price' => $offer->price, 'context' => 'recommendation',
    ]);
    RecommendationRun::create([
        'user_id' => $this->user->id, 'person_id' => $this->person->id, 'locale' => 'ro', 'status' => 'ready',
    ]);

    $data = $this->actingAs($this->user)->getJson('/api/v1/account/export')->assertOk()->json();
    $people = collect($data['people']);

    expect($data['format'])->toBe('wishio/export/2')
        ->and($data['account']['ai_consent_at'])->not->toBeNull()
        ->and($people->firstWhere('name', 'Alex')['avoids'][0]['text'])->toBe('alergic la nuci')
        ->and($people->firstWhere('name', 'Fost coleg')['deleted_at'])->not->toBeNull()
        ->and($data['devices'])->toHaveCount(1)
        ->and($data['devices'][0])->not->toHaveKey('token')
        ->and($data['clicks'][0]['product'])->toBe($offer->product->title)
        ->and($data['clicks'][0]['person'])->toBe('Alex')
        ->and($data['recommendations'][0]['person'])->toBe('Alex');
});
