<?php

use App\Domain\Occasions\Models\Occasion;
use App\Domain\People\Enums\FieldSource;
use App\Domain\People\Models\Person;
use App\Domain\Reminders\Actions\BuildWeeklyDigest;
use App\Domain\Reminders\Jobs\SendWeeklyDigests;
use App\Domain\Reminders\Mail\WeeklyDigest;
use App\Domain\Reminders\Models\UserSettings;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();

    $this->user = User::factory()->create([
        'name' => 'Maxim', 'locale' => 'ro', 'timezone' => 'Europe/Chisinau',
    ]);
    UserSettings::create(['user_id' => $this->user->id, 'email_digest' => true]);

    // Luni, 09:00 la Chișinău (UTC+3 vara).
    $this->mondayMorning = CarbonImmutable::parse('2026-09-07 06:00', 'UTC');
});

/**
 * Ocazie la N zile de AZI.
 *
 * Baza trebuie să fie ziua curentă, nu o dată fixă: `daysUntil()` se
 * raportează la azi, iar o bază fixă ar face testul să treacă sau să pice
 * în funcție de data la care e rulat.
 */
function occasionInDays(User $user, int $days): Occasion
{
    $date = CarbonImmutable::today()->addDays($days);
    $person = Person::create(['user_id' => $user->id, 'display_name' => 'Alex']);

    return Occasion::create([
        'user_id' => $user->id, 'person_id' => $person->id, 'type' => 'birthday',
        'month' => $date->month, 'day' => $date->day,
        'source' => FieldSource::OwnerManual->value, 'confirmed_at' => now(),
    ]);
}

it('trimite rezumatul luni dimineata, in fusul utilizatorului', function () {
    occasionInDays($this->user, 10);

    (new SendWeeklyDigests())->handle(app(BuildWeeklyDigest::class), $this->mondayMorning);

    Mail::assertSent(WeeklyDigest::class, fn ($mail) => $mail->hasTo($this->user->email));
});

it('nu trimite in alta zi sau alta ora', function (string $moment) {
    occasionInDays($this->user, 10);

    (new SendWeeklyDigests())->handle(app(BuildWeeklyDigest::class), CarbonImmutable::parse($moment, 'UTC'));

    Mail::assertNothingSent();
})->with([
    '2026-09-08 06:00',   // marți
    '2026-09-07 12:00',   // luni, dar la 15:00 local
    '2026-09-07 03:00',   // luni, dar la 06:00 local
]);

it('respecta fusul orar al fiecarui utilizator', function () {
    // Acelasi moment UTC e luni 09:00 la Chisinau, dar 06:00 la Lisabona.
    $other = User::factory()->create(['timezone' => 'Europe/Lisbon', 'locale' => 'en']);
    UserSettings::create(['user_id' => $other->id, 'email_digest' => true]);

    occasionInDays($this->user, 5);
    occasionInDays($other, 5);

    (new SendWeeklyDigests())->handle(app(BuildWeeklyDigest::class), $this->mondayMorning);

    Mail::assertSent(WeeklyDigest::class, 1);
    Mail::assertSent(WeeklyDigest::class, fn ($mail) => $mail->hasTo($this->user->email));
});

it('nu trimite un email gol', function () {
    // Un rezumat saptamanal fara continut transforma produsul in spam.
    (new SendWeeklyDigests())->handle(app(BuildWeeklyDigest::class), $this->mondayMorning);

    Mail::assertNothingSent();
});

it('nu trimite de doua ori in aceeasi saptamana', function () {
    occasionInDays($this->user, 10);

    // Jobul ruleaza din ora in ora; o repornire nu trebuie sa retrimita.
    (new SendWeeklyDigests())->handle(app(BuildWeeklyDigest::class), $this->mondayMorning);
    (new SendWeeklyDigests())->handle(app(BuildWeeklyDigest::class), $this->mondayMorning);

    Mail::assertSent(WeeklyDigest::class, 1);
});

it('trimite din nou saptamana urmatoare', function () {
    occasionInDays($this->user, 30);

    (new SendWeeklyDigests())->handle(app(BuildWeeklyDigest::class), $this->mondayMorning);
    (new SendWeeklyDigests())->handle(app(BuildWeeklyDigest::class), $this->mondayMorning->addWeek());

    Mail::assertSent(WeeklyDigest::class, 2);
});

it('nu trimite celor care au oprit rezumatul', function () {
    UserSettings::where('user_id', $this->user->id)->update(['email_digest' => false]);
    occasionInDays($this->user, 10);

    (new SendWeeklyDigests())->handle(app(BuildWeeklyDigest::class), $this->mondayMorning);

    Mail::assertNothingSent();
});

it('include doar ocaziile din orizontul de 30 de zile', function () {
    occasionInDays($this->user, 5);
    occasionInDays($this->user, 60);

    expect(app(BuildWeeklyDigest::class)($this->user))->toHaveCount(1);
});

it('exclude ocaziile oprite si cele care nu pot notifica', function () {
    occasionInDays($this->user, 5)->update(['is_muted' => true]);

    expect(app(BuildWeeklyDigest::class)($this->user))->toBeEmpty();
});

it('ordoneaza ocaziile dupa apropiere', function () {
    occasionInDays($this->user, 20);
    occasionInDays($this->user, 3);
    occasionInDays($this->user, 11);

    $days = app(BuildWeeklyDigest::class)($this->user)->pluck('daysUntil');

    expect($days->all())->toBe([3, 11, 20]);
});

it('foloseste limba utilizatorului in subiect', function (string $locale, string $expected) {
    $user = User::factory()->create(['locale' => $locale, 'timezone' => 'Europe/Chisinau']);
    UserSettings::create(['user_id' => $user->id, 'email_digest' => true]);
    occasionInDays($user, 5);

    $mail = new WeeklyDigest($user, app(BuildWeeklyDigest::class)($user));

    expect($mail->envelope()->subject)->toContain($expected);
})->with([
    ['ro', 'ocazie'],
    ['ru', 'событие'],
    ['en', 'occasion'],
]);

it('permite dezabonarea dintr-un singur click, fara autentificare', function () {
    $url = URL::signedRoute('digest.unsubscribe', ['user' => $this->user->id]);

    $this->get($url)->assertOk();

    expect($this->user->settings()->first()->email_digest)->toBeFalse();
});

it('respinge un link de dezabonare nesemnat', function () {
    $this->get("/digest/unsubscribe/{$this->user->id}")->assertForbidden();

    expect($this->user->settings()->first()->email_digest)->toBeTrue();
});
