<?php

use App\Domain\Validation\Models\GiftRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function validRequest(array $overrides = []): array
{
    return array_merge([
        'relationship' => 'friend',
        'age_bracket'  => '25_35',
        'about'        => 'Îi plac mașinile și merge la sală de trei ori pe săptămână.',
        'budget'       => '1000_2000',
        'occasion'     => 'birthday',
        'contact'      => '@testuser',
        'consent'      => '1',
    ], $overrides);
}

it('afiseaza landing-ul in toate cele trei limbi', function (string $locale, string $expected) {
    $this->withHeader('Accept-Language', $locale)
        ->get('/')
        ->assertOk()
        ->assertSee($expected, escape: false);
})->with([
    ['ro', 'Nu știi ce cadou să iei?'],
    ['ru', 'Не знаете, что подарить?'],
    ['en', 'know what to give?'],
]);

it('cade inapoi pe romana pentru o limba nesuportata', function () {
    $this->withHeader('Accept-Language', 'de')
        ->get('/')
        ->assertOk()
        ->assertSee('Nu știi ce cadou să iei?', escape: false);
});

it('cade inapoi pe romana cand lipseste headerul de limba', function () {
    // Symfony::create() injecteaza singur 'en-us,en;q=0.5', deci headerul trebuie
    // golit explicit ca sa reproducem o cerere reala fara Accept-Language.
    // Fara aceasta ramura, orice astfel de cerere ar primi engleza in loc de romana.
    $this->call('GET', '/', [], [], [], ['HTTP_ACCEPT_LANGUAGE' => ''])
        ->assertSee('Nu știi ce cadou să iei?', escape: false);
});

it('comuta limba si o tine minte', function () {
    $this->get('/lang/ru')->assertRedirect();

    $this->get('/')->assertSee('Не знаете, что подарить?', escape: false);
});

it('respinge o limba invalida in comutator', function () {
    $this->get('/lang/de')->assertNotFound();
});

it('salveaza cererea si duce la pagina de multumire', function () {
    $this->withHeader('Accept-Language', 'ro')
        ->post('/cerere', validRequest())
        ->assertRedirect(route('landing.thanks'));

    $request = GiftRequest::sole();

    expect($request->relationship)->toBe('friend')
        ->and($request->budget_min)->toBe(1000)
        ->and($request->budget_max)->toBe(2000)
        ->and($request->contact_channel)->toBe('telegram')
        ->and($request->locale)->toBe('ro');
});

it('cere consimtamant explicit', function () {
    // Fara bifa nu se salveaza nimic — docs/06-privacy-legal.md.
    $this->post('/cerere', validRequest(['consent' => null]))
        ->assertSessionHasErrors('consent');

    expect(GiftRequest::count())->toBe(0);
});

it('versioneaza consimtamantul acceptat', function () {
    // Dovada consimtamantului = ce text a fost acceptat si cand.
    $this->post('/cerere', validRequest());

    $request = GiftRequest::sole();

    expect($request->consent_version)->not->toBeEmpty()
        ->and($request->consented_at)->not->toBeNull();
});

it('nu stocheaza niciodata adresa IP in clar', function () {
    $this->post('/cerere', validRequest());

    $request = GiftRequest::sole();

    expect($request->ip_hash)->toHaveLength(64)
        ->and($request->ip_hash)->not->toContain('127.0.0.1');
});

it('cere descrierea persoanei si un contact', function () {
    $this->post('/cerere', validRequest(['about' => '', 'contact' => '']))
        ->assertSessionHasErrors(['about', 'contact']);
});

it('respinge o descriere prea scurta ca sa fie utila', function () {
    $this->post('/cerere', validRequest(['about' => 'nush']))
        ->assertSessionHasErrors('about');
});

it('respinge o data a ocaziei din trecut', function () {
    $this->post('/cerere', validRequest(['occasion_date' => now()->subDay()->toDateString()]))
        ->assertSessionHasErrors('occasion_date');
});

it('deduce canalul de contact', function (string $contact, string $expected) {
    $this->post('/cerere', validRequest(['contact' => $contact]));

    expect(GiftRequest::sole()->contact_channel)->toBe($expected);
})->with([
    ['ion@example.com', 'email'],
    ['@ionutz', 'telegram'],
    ['+373 69 123 456', 'phone'],
]);

it('limiteaza numarul de cereri de la acelasi IP', function () {
    foreach (range(1, 5) as $n) {
        $this->post('/cerere', validRequest())->assertRedirect();
    }

    $this->post('/cerere', validRequest())->assertStatus(429);

    expect(GiftRequest::count())->toBe(5);
});

it('nu indexeaza landing-ul cat suntem in Faza 0', function () {
    $this->get('/')->assertSee('name="robots" content="noindex"', escape: false);
});
