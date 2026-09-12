<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Retry-After se calculeaza fata de ceas; inghetat, fereastra de 10 minute
    // da exact 600 de secunde.
    $this->freezeTime();

    // O cerere noua porneste cu limba din config. In teste aplicatia ramane
    // aceeasi de la o cerere la alta, iar SetLocale lasa in urma limba celei
    // precedente: fara readucerea ei inaintea cererii limitate, testele ar
    // trece si daca randarea n-ar rezolva singura limba.
    $this->bootLocale = app()->getLocale();
});

it('traduce mesajul de limitare in limba cererii', function (string $locale, string $expected) {
    // Aplicatia mobila afiseaza `message` ca atare. Pana acum framework-ul
    // raspundea mereu „Too Many Attempts.”, deci RO si RU vedeau engleza.
    foreach (range(1, 10) as $n) {
        $this->postJson('/api/v1/auth/login', ['email' => 'a@b.com', 'password' => 'x']);
    }

    app()->setLocale($this->bootLocale);

    $this->withHeader('Accept-Language', $locale)
        ->postJson('/api/v1/auth/login', ['email' => 'a@b.com', 'password' => 'x'])
        ->assertStatus(429)
        ->assertExactJson(['message' => $expected])
        ->assertHeader('Retry-After', 600)
        ->assertHeader('X-RateLimit-Limit', 10)
        ->assertHeader('X-RateLimit-Remaining', 0)
        ->assertHeader('X-RateLimit-Reset', now()->addSeconds(600)->getTimestamp());
})->with([
    ['ro', 'Prea multe încercări. Încearcă din nou peste 600 de secunde.'],
    ['ru', 'Слишком много попыток. Попробуйте снова через 600 сек.'],
    ['en', 'Too many attempts. Try again in 600 seconds.'],
]);

it('foloseste forma de plural corecta in romana', function (int $seconds, string $expected) {
    // De la 20 in sus se spune „de secunde” — mai putin cand ultimele doua
    // cifre sunt 01–19: „105 secunde”.
    foreach (range(1, 10) as $n) {
        $this->postJson('/api/v1/auth/login', ['email' => 'a@b.com', 'password' => 'x']);
    }

    $this->travel(600 - $seconds)->seconds();

    $this->withHeader('Accept-Language', 'ro')
        ->postJson('/api/v1/auth/login', ['email' => 'a@b.com', 'password' => 'x'])
        ->assertHeader('Retry-After', $seconds)
        ->assertJsonPath('message', "Prea multe încercări. Încearcă din nou peste $expected.");
})->with([
    [1, 'o secundă'],
    [19, '19 secunde'],
    [20, '20 de secunde'],
    [105, '105 secunde'],
]);

it('traduce si pagina de limitare a formularelor web', function () {
    // Pe web limba vine din comutatorul din pagina, pastrat in sesiune.
    $this->withSession(['locale' => 'ru']);

    // Limitarea ruleaza inaintea validarii: si trimiterile goale consuma din limita.
    foreach (range(1, 5) as $n) {
        $this->post('/cerere');
    }

    app()->setLocale($this->bootLocale);

    $this->post('/cerere')
        ->assertStatus(429)
        ->assertHeader('Retry-After', 600)
        ->assertHeader('X-RateLimit-Limit', 5)
        ->assertSee('<html lang="ru">', escape: false)
        ->assertSee('Слишком много попыток. Попробуйте снова через 600 сек.', escape: false)
        ->assertDontSee('Too Many');
});
