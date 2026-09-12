<?php

use App\Domain\Occasions\Models\Occasion;
use App\Domain\People\Enums\FieldSource;
use App\Domain\People\Models\Person;
use App\Domain\Reminders\Actions\ScheduleReminders;
use App\Domain\Reminders\Models\QueuedNotification;
use App\Domain\Reminders\Models\UserSettings;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['timezone' => 'Europe/Chisinau', 'locale' => 'ro']);
    $this->schedule = app(ScheduleReminders::class);
    $this->now = CarbonImmutable::parse('2026-09-01 06:00', 'UTC');
});

function occasionIn(User $user, int $days, string $type = 'birthday', array $attributes = []): Occasion
{
    $date = CarbonImmutable::parse('2026-09-01', 'Europe/Chisinau')->addDays($days);
    $person = Person::create(['user_id' => $user->id, 'display_name' => 'Alex']);

    return Occasion::create(array_merge([
        'user_id'      => $user->id,
        'person_id'    => $person->id,
        'type'         => $type,
        'month'        => $date->month,
        'day'          => $date->day,
        'source'       => FieldSource::OwnerManual->value,
        'confidence'   => 0.9,
        'confirmed_at' => now(),
    ], $attributes));
}

it('planifica remindere pentru pragurile configurate', function () {
    occasionIn($this->user, 10);

    ($this->schedule)($this->user, $this->now);

    // Implicit 7, 3, 1 plus ziua respectiva.
    expect(QueuedNotification::pluck('days_before')->sort()->values()->all())->toBe([0, 1, 3, 7]);
});

it('nu planifica remindere in trecut', function () {
    // Ocazia e peste 2 zile: pragurile de 7 si 3 zile au trecut deja.
    occasionIn($this->user, 2);

    ($this->schedule)($this->user, $this->now);

    expect(QueuedNotification::pluck('days_before')->sort()->values()->all())->toBe([0, 1]);
});

it('respecta ora preferata, in fusul utilizatorului', function () {
    UserSettings::create(['user_id' => $this->user->id, 'preferred_hour' => 9]);
    occasionIn($this->user, 10);

    ($this->schedule)($this->user, $this->now);

    $notification = QueuedNotification::where('days_before', 7)->sole();

    expect($notification->scheduled_for->setTimezone('Europe/Chisinau')->format('H:i'))->toBe('09:00');
});

it('muta notificarea in afara orelor de liniste', function () {
    // O notificare la 3 dimineata nu e un reminder.
    UserSettings::create([
        'user_id' => $this->user->id, 'preferred_hour' => 3, 'quiet_from' => 22, 'quiet_to' => 8,
    ]);
    occasionIn($this->user, 10);

    ($this->schedule)($this->user, $this->now);

    $hour = QueuedNotification::first()->scheduled_for->setTimezone('Europe/Chisinau')->hour;

    expect($hour)->toBe(8);
});

it('limiteaza notificarile pe zi', function () {
    // Trei ocazii in aceeasi zi ar produce trei remindere simultan.
    foreach (range(1, 3) as $n) {
        occasionIn($this->user, 7);
    }

    ($this->schedule)($this->user, $this->now);

    $perDay = QueuedNotification::get()
        ->groupBy(fn ($n) => $n->scheduled_for->setTimezone('Europe/Chisinau')->toDateString())
        ->map->count();

    expect($perDay->max())->toBeLessThanOrEqual(config('wishio.reminders.max_push_per_day'));
});

it('nu depaseste numarul maxim de notificari per ocazie', function () {
    UserSettings::create(['user_id' => $this->user->id, 'reminder_days' => [14, 7, 3, 1]]);
    occasionIn($this->user, 20);

    ($this->schedule)($this->user, $this->now);

    expect(QueuedNotification::count())->toBeLessThanOrEqual(config('wishio.reminders.max_per_occasion'));
});

it('nu creeaza duplicate la rulari repetate', function () {
    occasionIn($this->user, 10);

    foreach (range(1, 3) as $n) {
        ($this->schedule)($this->user, $this->now);
    }

    expect(QueuedNotification::count())->toBe(4);
});

it('nu planifica pentru ocazii care nu au voie sa notifice', function () {
    // Onomastica dedusa, neconfirmata si neverificata — docs/15 § 4.
    occasionIn($this->user, 10, 'name_day', [
        'source'       => FieldSource::Derived->value,
        'confidence'   => 0.6,
        'confirmed_at' => null,
    ]);

    ($this->schedule)($this->user, $this->now);

    expect(QueuedNotification::count())->toBe(0);
});

it('nu planifica pentru ocazii oprite din notificari', function () {
    occasionIn($this->user, 10, 'birthday', ['is_muted' => true]);

    ($this->schedule)($this->user, $this->now);

    expect(QueuedNotification::count())->toBe(0);
});

it('nu planifica daca utilizatorul a oprit push-ul', function () {
    UserSettings::create(['user_id' => $this->user->id, 'push_enabled' => false]);
    occasionIn($this->user, 10);

    ($this->schedule)($this->user, $this->now);

    expect(QueuedNotification::count())->toBe(0);
});

it('fixeaza limba la planificare, nu la trimitere', function () {
    // Daca utilizatorul schimba limba intre timp, notificarea deja programata
    // ramane coerenta cu ce se astepta cand a programat-o.
    occasionIn($this->user, 10);
    ($this->schedule)($this->user, $this->now);

    $this->user->update(['locale' => 'ru']);

    expect(QueuedNotification::first()->locale)->toBe('ro');
});

it('trece la anul urmator daca ocazia a trecut', function () {
    // Ocazia a fost acum o luna; urmatoarea aparitie e peste 11 luni.
    $person = Person::create(['user_id' => $this->user->id, 'display_name' => 'Ana']);
    Occasion::create([
        'user_id' => $this->user->id, 'person_id' => $person->id, 'type' => 'birthday',
        'month'   => 8, 'day' => 1, 'source' => FieldSource::OwnerManual->value, 'confirmed_at' => now(),
    ]);

    ($this->schedule)($this->user, $this->now);

    // Dincolo de orizontul de planificare — nimic acum, se va prinde mai tarziu.
    expect(QueuedNotification::count())->toBe(0);
});
