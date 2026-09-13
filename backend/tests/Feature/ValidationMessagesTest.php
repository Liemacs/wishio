<?php

use App\Domain\People\Models\Person;
use App\Domain\Profiles\Models\PublicProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('traduce mesajele de validare in toate cele trei limbi', function (string $locale, string $password, string $days) {
    // Pana acum, RO si RU vedeau cheia bruta („validation.min.string”) pe telefon.
    $this->withHeader('Accept-Language', $locale)
        ->postJson('/api/v1/auth/register', ['name' => 'Ana', 'email' => 'ana@example.com', 'password' => 'scurt'])
        ->assertJsonValidationErrors(['password' => $password]);

    $this->actingAs(User::factory()->create())
        ->patchJson('/api/v1/settings', ['reminder_days' => [14, 7, 3, 1]])
        ->assertJsonValidationErrors(['reminder_days' => $days]);
})->with([
    ['ro', 'Câmpul „parolă” trebuie să aibă minimum 8 caractere.', 'La câmpul „când te anunțăm” poți alege cel mult 3.'],
    ['ru', 'Длина поля «пароль» должна быть не меньше 8 симв.', 'В поле «когда напоминать» можно выбрать не больше 3.'],
    ['en', 'The password field must be at least 8 characters.', 'The reminder days field must not have more than 3 items.'],
]);

it('spune in limba omului ca emailul are deja cont', function (string $locale, string $expected) {
    User::factory()->create(['email' => 'ana@example.com']);

    $this->withHeader('Accept-Language', $locale)
        ->postJson('/api/v1/auth/register', ['name' => 'Ana', 'email' => 'ana@example.com', 'password' => 'parola-buna'])
        ->assertJsonValidationErrors(['email' => $expected]);
})->with([
    ['ro', 'Există deja un cont cu acest email.'],
    ['ru', 'Аккаунт с таким email уже существует.'],
    ['en', 'An account with this email already exists.'],
]);

it('nu arata niciodata o cheie bruta sau un mesaj in engleza in romana si rusa', function (string $locale) {
    $this->withHeader('Accept-Language', $locale);

    $user = User::factory()->create();
    $person = Person::create(['user_id' => $user->id, 'display_name' => 'Alex']);
    $profile = PublicProfile::create([
        'user_id'      => $user->id, 'slug' => PublicProfile::generateSlug('Ana'),
        'display_name' => 'Ana', 'locale' => 'ro', 'visibility' => PublicProfile::DEFAULT_VISIBILITY,
    ]);

    $messages = collect();
    $collect = fn ($response) => $messages->push(...collect($response->assertUnprocessable()->json('errors'))->flatten()->all());

    $collect($this->postJson('/api/v1/auth/register', []));
    $collect($this->postJson('/api/v1/auth/register', ['name' => str_repeat('a', 200), 'email' => 'nu-e-email', 'password' => 'x']));

    $this->actingAs($user);
    $collect($this->postJson('/api/v1/people', []));
    $collect($this->patchJson("/api/v1/people/{$person->id}", ['budget_min' => -5, 'birth_date' => 'maine', 'gender' => 'x']));
    $collect($this->patchJson('/api/v1/settings', ['reminder_days' => [2, 2], 'preferred_hour' => 30]));
    $collect($this->patchJson('/api/v1/auth/me', ['timezone' => 'Mars/Olympus_Mons', 'locale' => 'de']));
    $collect($this->deleteJson('/api/v1/account', []));
    $collect($this->postJson('/api/v1/wishlist', ['kind' => 'nava']));

    // Formularul public se afiseaza pe web, tot in limba vizitatorului.
    $this->post("/@{$profile->slug}", ['interests' => ['inventat']])->assertSessionHasErrors();
    $messages->push(...session('errors')->getBag('default')->all());

    expect($messages)->not->toBeEmpty();

    foreach ($messages as $message) {
        expect($message)->not->toStartWith('validation.')
            ->and($message)->not->toMatch('/\bThe .+ (field|has already)/')
            ->and($message)->not->toMatch('/\b(display name|budget min|birth date|reminder days|preferred hour|confirm email|password|timezone|locale|kind)\b/');
    }
})->with(['ro', 'ru']);
