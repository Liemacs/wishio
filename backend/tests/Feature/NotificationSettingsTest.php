<?php

use App\Domain\Occasions\Models\Occasion;
use App\Domain\People\Enums\FieldSource;
use App\Domain\People\Models\Person;
use App\Domain\Reminders\Actions\ScheduleReminders;
use App\Domain\Reminders\Jobs\RescheduleRemindersForUser;
use App\Domain\Reminders\Jobs\SendDueNotifications;
use App\Domain\Reminders\Models\DeviceToken;
use App\Domain\Reminders\Models\QueuedNotification;
use App\Domain\Reminders\Models\UserSettings;
use App\Models\User;
use App\Support\Push\NullPushSender;
use App\Support\Push\PushSender;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->app->instance(PushSender::class, new NullPushSender);
    $this->travelTo(CarbonImmutable::parse('2026-09-01 06:00', 'UTC'));

    $this->user = User::factory()->create(['timezone' => 'Europe/Chisinau', 'locale' => 'ro']);
    UserSettings::create(['user_id' => $this->user->id]);
});

function birthdayInDays(User $user, int $days): Occasion
{
    $date = CarbonImmutable::now()->setTimezone($user->timezone)->addDays($days);
    $person = Person::create(['user_id' => $user->id, 'display_name' => 'Alex']);

    return Occasion::create([
        'user_id' => $user->id, 'person_id' => $person->id, 'type' => 'birthday',
        'month'   => $date->month, 'day' => $date->day,
        'source'  => FieldSource::OwnerManual->value, 'confidence' => 0.9, 'confirmed_at' => now(),
    ]);
}

/** Treptele planificate și încă neexpediate. */
function plannedThresholds(User $user): array
{
    return QueuedNotification::where('user_id', $user->id)->whereNull('sent_at')
        ->pluck('days_before')->sort()->values()->all();
}

function settingsLocalTime(string $timezone, int $daysBefore = 7): string
{
    return QueuedNotification::where('days_before', $daysBefore)->sole()
        ->scheduled_for->setTimezone($timezone)->format('H:i');
}

it('muta notificarile deja planificate cand se schimba ora', function () {
    birthdayInDays($this->user, 20);
    app(ScheduleReminders::class)($this->user);

    $this->actingAs($this->user)->patchJson('/api/v1/settings', ['preferred_hour' => 18])->assertOk();

    expect(settingsLocalTime('Europe/Chisinau'))->toBe('18:00');
});

it('scoate treptele renuntate si le planifica pe cele noi', function () {
    birthdayInDays($this->user, 20);
    app(ScheduleReminders::class)($this->user);

    expect(plannedThresholds($this->user))->toBe([0, 1, 3, 7]);

    $this->actingAs($this->user)->patchJson('/api/v1/settings', ['reminder_days' => [14, 1]])->assertOk();

    expect(plannedThresholds($this->user))->toBe([0, 1, 14]);
});

it('opreste notificarile planificate cand pushul e dezactivat', function () {
    // Pana acum, cine oprea notificarile le primea in continuare pana la 35 de zile.
    birthdayInDays($this->user, 20);
    app(ScheduleReminders::class)($this->user);
    QueuedNotification::where('days_before', 7)->update(['sent_at' => now()]);

    $this->actingAs($this->user)->patchJson('/api/v1/settings', ['push_enabled' => false])->assertOk();

    // Ce s-a trimis ramane: altfel treapta ar putea fi retrimisa.
    expect(plannedThresholds($this->user))->toBe([])
        ->and(QueuedNotification::whereNotNull('sent_at')->count())->toBe(1);
});

it('nu trimite o notificare scadenta daca pushul a fost oprit intre timp', function () {
    DeviceToken::create(['user_id' => $this->user->id, 'token' => 'ExponentPushToken[m6]', 'platform' => 'ios']);
    $occasion = birthdayInDays($this->user, 1);

    $notification = QueuedNotification::create([
        'user_id'       => $this->user->id, 'occasion_id' => $occasion->id, 'channel' => 'push',
        'days_before'   => 1, 'occasion_year' => 2026, 'locale' => 'ro',
        'scheduled_for' => now()->subMinute(),
    ]);

    // Direct pe model, fara replanificare: minutele dintre planificare si trimitere.
    UserSettings::where('user_id', $this->user->id)->update(['push_enabled' => false]);

    app()->call([new SendDueNotifications, 'handle']);

    expect($notification->fresh()->failure)->toBe('push_disabled')
        ->and($notification->fresh()->sent_at)->not->toBeNull();
});

it('accepta doar treptele din scara, cel mult trei', function (array $days) {
    $this->actingAs($this->user)->patchJson('/api/v1/settings', ['reminder_days' => $days])
        ->assertUnprocessable();
})->with([
    'treapta inexistenta' => [[2]],
    'patru trepte'        => [[14, 7, 3, 1]],
    'dubluri'             => [[7, 7]],
]);

it('replanifica doar cand se schimba ceva ce priveste pushul', function () {
    Queue::fake();

    $this->actingAs($this->user)->patchJson('/api/v1/settings', ['email_digest' => false])->assertOk();
    // Aceeasi ora ca inainte: nimic de replanificat.
    $this->actingAs($this->user)->patchJson('/api/v1/settings', ['preferred_hour' => 10])->assertOk();

    Queue::assertNotPushed(RescheduleRemindersForUser::class);

    $this->actingAs($this->user)->patchJson('/api/v1/settings', ['quiet_from' => 23])->assertOk();

    Queue::assertPushed(RescheduleRemindersForUser::class, 1);
});

it('respinge un fus orar inexistent', function () {
    $this->actingAs($this->user)->patchJson('/api/v1/auth/me', ['timezone' => 'Mars/Olympus_Mons'])
        ->assertJsonValidationErrors('timezone');
});

it('replanifica la schimbarea fusului orar', function () {
    birthdayInDays($this->user, 20);
    app(ScheduleReminders::class)($this->user);

    $this->actingAs($this->user)->patchJson('/api/v1/auth/me', ['timezone' => 'Europe/Rome'])->assertOk();

    // 10:00 ramane ora de pe ceasul utilizatorului, in noul fus.
    expect(settingsLocalTime('Europe/Rome'))->toBe('10:00');
});
