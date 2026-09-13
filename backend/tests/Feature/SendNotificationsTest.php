<?php

use App\Domain\Occasions\Models\Occasion;
use App\Domain\People\Enums\FieldSource;
use App\Domain\People\Models\Person;
use App\Domain\Reminders\Actions\BuildReminderContent;
use App\Domain\Reminders\Jobs\SendDueNotifications;
use App\Domain\Reminders\Models\DeviceToken;
use App\Domain\Reminders\Models\QueuedNotification;
use App\Models\User;
use App\Support\Push\NullPushSender;
use App\Support\Push\PushSender;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->sender = new NullPushSender;
    $this->app->instance(PushSender::class, $this->sender);

    $this->user = User::factory()->create(['locale' => 'ro', 'timezone' => 'Europe/Chisinau']);
    DeviceToken::create([
        'user_id' => $this->user->id, 'token' => 'ExponentPushToken[abc]', 'platform' => 'ios',
    ]);
});

function queued(User $user, int $daysBefore, array $occasionAttributes = [], array $attributes = []): QueuedNotification
{
    $person = Person::create(['user_id' => $user->id, 'display_name' => 'Alex']);

    $occasion = Occasion::create(array_merge([
        'user_id' => $user->id, 'person_id' => $person->id, 'type' => 'birthday',
        'month'   => 12, 'day' => 25, 'source' => FieldSource::OwnerManual->value, 'confirmed_at' => now(),
    ], $occasionAttributes));

    return QueuedNotification::create(array_merge([
        'user_id'       => $user->id, 'occasion_id' => $occasion->id, 'channel' => 'push',
        'days_before'   => $daysBefore, 'occasion_year' => 2026, 'locale' => $user->locale,
        'scheduled_for' => now()->subMinute(),
    ], $attributes));
}

it('trimite notificarile ajunse la scadenta', function () {
    queued($this->user, 1);

    (new SendDueNotifications)->handle($this->sender, app(BuildReminderContent::class));

    expect($this->sender->sent)->toHaveCount(1)
        ->and($this->sender->sent[0]->token)->toBe('ExponentPushToken[abc]')
        ->and($this->sender->sent[0]->title)->toContain('Alex')
        ->and(QueuedNotification::sole()->sent_at)->not->toBeNull();
});

it('nu trimite inainte de momentul planificat', function () {
    queued($this->user, 1, [], ['scheduled_for' => now()->addHour()]);

    (new SendDueNotifications)->handle($this->sender, app(BuildReminderContent::class));

    expect($this->sender->sent)->toBeEmpty()
        ->and(QueuedNotification::sole()->sent_at)->toBeNull();
});

it('nu trimite remindere expirate', function () {
    // Ocazia a trecut deja; un reminder vechi de doua zile e doar zgomot.
    queued($this->user, 1, [], ['scheduled_for' => now()->subDays(2)]);

    (new SendDueNotifications)->handle($this->sender, app(BuildReminderContent::class));

    expect($this->sender->sent)->toBeEmpty();
});

it('nu retrimite ce a fost deja trimis', function () {
    queued($this->user, 1);

    foreach (range(1, 3) as $n) {
        (new SendDueNotifications)->handle($this->sender, app(BuildReminderContent::class));
    }

    expect($this->sender->sent)->toHaveCount(1);
});

it('nu trimite daca ocazia a fost oprita intre timp', function () {
    $notification = queued($this->user, 1, ['is_muted' => true]);

    (new SendDueNotifications)->handle($this->sender, app(BuildReminderContent::class));

    expect($this->sender->sent)->toBeEmpty()
        ->and($notification->fresh()->failure)->toBe('occasion_no_longer_notifiable');
});

it('foloseste limba fixata la planificare', function () {
    // Utilizatorul a trecut pe engleza, dar notificarea fusese programata in rusa.
    $this->user->update(['locale' => 'en']);
    queued($this->user, 0, [], ['locale' => 'ru']);

    (new SendDueNotifications)->handle($this->sender, app(BuildReminderContent::class));

    expect($this->sender->sent[0]->title)->toContain('сегодня');
});

it('construieste mesaje diferite pe fiecare treapta', function (int $daysBefore, string $expected) {
    // Fiecare treapta spune ALTCEVA — docs/09 § 3. O serie care repeta acelasi
    // mesaj de patru ori devine zgomot.
    $person = Person::create(['user_id' => $this->user->id, 'display_name' => 'Alex']);
    $occasion = Occasion::create([
        'user_id' => $this->user->id, 'person_id' => $person->id, 'type' => 'birthday',
        'month'   => 12, 'day' => 25, 'source' => FieldSource::OwnerManual->value, 'confirmed_at' => now(),
    ]);

    $content = app(BuildReminderContent::class)($occasion->load('person'), $daysBefore, 'ro');

    expect($content['title'])->toContain($expected)->toContain('Alex');
})->with([
    [0, 'astăzi'],
    [1, 'mâine'],
    [3, 'peste 3 zile'],
    [7, 'peste 7 zile'],
]);

it('tine titlul scurt, ca iOS sa nu-l taie, si pune invitatia la cadou in corp', function (string $locale, string $title, string $ask) {
    // Inainte: „Alexandra are zi de naștere peste 14 zile. Găsim un cadou?” — 58 de caractere.
    $person = Person::create(['user_id' => $this->user->id, 'display_name' => 'Alexandra']);
    $occasion = Occasion::create([
        'user_id' => $this->user->id, 'person_id' => $person->id, 'type' => 'birthday',
        'month'   => 12, 'day' => 25, 'source' => FieldSource::OwnerManual->value, 'confirmed_at' => now(),
    ]);

    $content = app(BuildReminderContent::class)($occasion->load('person'), 14, $locale);

    expect($content['title'])->toBe($title)
        ->and(mb_strlen($content['title']))->toBeLessThanOrEqual(40)
        ->and($content['body'])->toBe($ask);
})->with([
    ['ro', 'Alexandra are ziua peste 14 zile', 'Găsim un cadou?'],
    ['ru', 'У Alexandra день рождения через 14 дней', 'Подберём подарок?'],
    ['en', "Alexandra's birthday is in 14 days", 'Shall we find a gift?'],
]);

it('include date de rutare, ca apasarea sa duca la persoana', function () {
    $notification = queued($this->user, 1);

    (new SendDueNotifications)->handle($this->sender, app(BuildReminderContent::class));

    expect($this->sender->sent[0]->data)
        ->toHaveKeys(['occasion_id', 'person_id', 'type']);
});

it('inregistreaza si sterge dispozitive', function () {
    $this->actingAs($this->user)
        ->postJson('/api/v1/devices', ['token' => 'ExponentPushToken[xyz]', 'platform' => 'android'])
        ->assertNoContent();

    expect(DeviceToken::where('token', 'ExponentPushToken[xyz]')->exists())->toBeTrue();

    $this->actingAs($this->user)
        ->deleteJson('/api/v1/devices', ['token' => 'ExponentPushToken[xyz]'])
        ->assertNoContent();

    expect(DeviceToken::where('token', 'ExponentPushToken[xyz]')->exists())->toBeFalse();
});

it('muta tokenul pe contul curent daca telefonul e partajat', function () {
    $other = User::factory()->create();

    $this->actingAs($other)->postJson('/api/v1/devices', [
        'token' => 'ExponentPushToken[abc]', 'platform' => 'ios',
    ])->assertNoContent();

    expect(DeviceToken::where('token', 'ExponentPushToken[abc]')->sole()->user_id)->toBe($other->id)
        ->and(DeviceToken::count())->toBe(1);
});

it('citeste si actualizeaza preferintele de notificare', function () {
    $this->actingAs($this->user)->getJson('/api/v1/settings')
        ->assertOk()
        ->assertJsonPath('data.reminder_days', [7, 3, 1])
        ->assertJsonPath('data.push_enabled', true)
        ->assertJsonPath('data.has_device', true);

    $this->actingAs($this->user)
        ->patchJson('/api/v1/settings', ['reminder_days' => [14, 1], 'preferred_hour' => 9])
        ->assertOk()
        ->assertJsonPath('data.reminder_days', [14, 1])
        ->assertJsonPath('data.preferred_hour', 9);
});
