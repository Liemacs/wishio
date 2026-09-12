<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('inregistreaza un utilizator si intoarce un token', function () {
    $this->postJson('/api/v1/auth/register', [
        'name' => 'Maxim', 'email' => 'maxim@example.com', 'password' => 'parola-buna',
    ])
        ->assertCreated()
        ->assertJsonPath('data.email', 'maxim@example.com')
        ->assertJsonStructure(['token', 'data' => ['id', 'locale', 'name_day_calendar']]);
});

it('alege calendarul de onomastici dupa limba', function (string $locale, string $calendar) {
    // Vorbitorii de rusa din Moldova urmeaza in general stilul vechi.
    $this->postJson('/api/v1/auth/register', [
        'name' => 'X', 'email' => "$locale@example.com", 'password' => 'parola-buna', 'locale' => $locale,
    ])->assertJsonPath('data.name_day_calendar', $calendar);
})->with([
    ['ro', 'orthodox_new'],
    ['ru', 'orthodox_old'],
    ['en', 'orthodox_new'],
]);

it('nu accepta acelasi email de doua ori', function () {
    User::factory()->create(['email' => 'ana@example.com']);

    $this->postJson('/api/v1/auth/register', [
        'name' => 'Ana', 'email' => 'ana@example.com', 'password' => 'parola-buna',
    ])->assertJsonValidationErrors('email');
});

it('cere o parola de cel putin opt caractere', function () {
    $this->postJson('/api/v1/auth/register', [
        'name' => 'Ana', 'email' => 'ana@example.com', 'password' => 'scurt',
    ])->assertJsonValidationErrors('password');
});

it('autentifica si intoarce un token', function () {
    User::factory()->create(['email' => 'ana@example.com', 'password' => 'parola-buna']);

    $this->postJson('/api/v1/auth/login', ['email' => 'ana@example.com', 'password' => 'parola-buna'])
        ->assertOk()
        ->assertJsonStructure(['token', 'data']);
});

it('da acelasi raspuns pentru email inexistent si parola gresita', function () {
    // Altfel endpointul spune atacatorului care adrese sunt inregistrate.
    User::factory()->create(['email' => 'ana@example.com', 'password' => 'parola-buna']);

    $wrongPassword = $this->postJson('/api/v1/auth/login', ['email' => 'ana@example.com', 'password' => 'gresita']);
    $unknownEmail = $this->postJson('/api/v1/auth/login', ['email' => 'nimeni@example.com', 'password' => 'gresita']);

    $wrongPassword->assertStatus(422);
    $unknownEmail->assertStatus(422);

    expect($unknownEmail->json('errors'))->toBe($wrongPassword->json('errors'));
});

it('accepta tokenul pe rutele protejate', function () {
    $user = User::factory()->create(['email' => 'ana@example.com', 'password' => 'parola-buna']);
    $token = $this->postJson('/api/v1/auth/login', ['email' => 'ana@example.com', 'password' => 'parola-buna'])->json('token');

    $this->withHeader('Authorization', "Bearer $token")
        ->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.id', $user->id);
});

it('invalideaza tokenul la delogare', function () {
    $user = User::factory()->create(['email' => 'ana@example.com', 'password' => 'parola-buna']);
    $token = $this->postJson('/api/v1/auth/login', ['email' => 'ana@example.com', 'password' => 'parola-buna'])->json('token');

    $this->withHeader('Authorization', "Bearer $token")->postJson('/api/v1/auth/logout')->assertNoContent();

    expect($user->tokens()->count())->toBe(0);

    // Guard-ul retine utilizatorul rezolvat in cadrul aceluiasi test;
    // fara asta, cererea urmatoare ar folosi sesiunea deja autentificata
    // si testul ar trece fals.
    $this->app['auth']->forgetGuards();

    $this->withHeader('Authorization', "Bearer $token")
        ->withHeader('Accept', 'application/json')
        ->getJson('/api/v1/auth/me')
        ->assertUnauthorized();
});

it('permite schimbarea limbii si a calendarului', function () {
    $user = User::factory()->create(['locale' => 'ro']);

    $this->actingAs($user)
        ->patchJson('/api/v1/auth/me', ['locale' => 'ru', 'name_day_calendar' => 'orthodox_old'])
        ->assertOk()
        ->assertJsonPath('data.locale', 'ru')
        ->assertJsonPath('data.name_day_calendar', 'orthodox_old');
});

it('limiteaza incercarile de autentificare', function () {
    foreach (range(1, 10) as $n) {
        $this->postJson('/api/v1/auth/login', ['email' => 'a@b.com', 'password' => 'x']);
    }

    $this->postJson('/api/v1/auth/login', ['email' => 'a@b.com', 'password' => 'x'])->assertStatus(429);
});

it('traduce mesajul de autentificare esuata in toate cele trei limbi', function (string $locale, string $expected) {
    // Pana acum, RO si RU vedeau cheia bruta `auth.failed` pe ecranul de login.
    User::factory()->create(['email' => 'ana@example.com', 'password' => 'parola-buna']);

    $this->withHeader('Accept-Language', $locale)
        ->postJson('/api/v1/auth/login', ['email' => 'ana@example.com', 'password' => 'gresita'])
        ->assertJsonValidationErrors(['email' => $expected]);
})->with([
    ['ro', 'Emailul sau parola nu sunt corecte.'],
    ['ru', 'Неверный email или пароль.'],
    ['en', 'The email or password is incorrect.'],
]);
