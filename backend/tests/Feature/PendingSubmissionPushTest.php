<?php

use App\Domain\People\Actions\ImportContacts;
use App\Domain\People\Models\Person;
use App\Domain\Profiles\Jobs\NotifyOwnersOfPendingSubmissions;
use App\Domain\Profiles\Models\ProfileSubmission;
use App\Domain\Profiles\Models\PublicProfile;
use App\Domain\Reminders\Models\DeviceToken;
use App\Domain\Reminders\Models\QueuedNotification;
use App\Domain\Reminders\Models\UserSettings;
use App\Models\User;
use App\Support\Push\NullPushSender;
use App\Support\Push\PushSender;
use Carbon\CarbonImmutable;
use Database\Seeders\InterestSeeder;
use Database\Seeders\NameDaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(InterestSeeder::class);
    $this->seed(NameDaySeeder::class);

    $this->sender = new NullPushSender;
    $this->app->instance(PushSender::class, $this->sender);

    // 12:00 la Chișinău: în afara orelor de liniște.
    $this->travelTo(CarbonImmutable::parse('2026-09-14 09:00', 'UTC'));

    $this->owner = User::factory()->create(['locale' => 'ro', 'timezone' => 'Europe/Chisinau']);
    UserSettings::create(['user_id' => $this->owner->id]);
    DeviceToken::create(['user_id' => $this->owner->id, 'token' => 'ExponentPushToken[owner]', 'platform' => 'ios']);

    $this->profile = PublicProfile::create([
        'user_id'      => $this->owner->id, 'slug' => PublicProfile::generateSlug('Maxim'),
        'display_name' => 'Maxim', 'locale' => 'ro', 'visibility' => PublicProfile::DEFAULT_VISIBILITY,
    ]);

    // Contactele „Ana” fac ca orice completare „Ana …” să aștepte alegerea.
    app(ImportContacts::class)($this->owner, [
        ['device_contact_id' => 'agenda-ana-rusu', 'display_name' => 'Ana Rusu', 'birth_date' => '1990-01-10', 'birth_year_known' => true],
        ['device_contact_id' => 'agenda-ana-ciobanu', 'display_name' => 'Ana Ciobanu'],
    ]);
});

function pendingFrom(PublicProfile $profile, string $name): ProfileSubmission
{
    test()->post("/@{$profile->slug}", [
        'display_name' => $name, 'birthday' => '05.05.1995', 'consent' => '1',
    ])->assertRedirect();

    return ProfileSubmission::latest('id')->first();
}

function runPendingPush(): void
{
    app()->call([new NotifyOwnersOfPendingSubmissions, 'handle']);
}

it('anunta proprietarul cand o completare asteapta raspunsul lui', function () {
    $submission = pendingFrom($this->profile, 'Ana Popescu');

    $this->travel(NotifyOwnersOfPendingSubmissions::GRACE_MINUTES + 1)->minutes();
    runPendingPush();

    expect($this->sender->sent)->toHaveCount(1)
        ->and($this->sender->sent[0]->token)->toBe('ExponentPushToken[owner]')
        ->and($this->sender->sent[0]->title)->toBe('Ana Popescu ți-a completat linkul')
        ->and($this->sender->sent[0]->data)->toBe(['type' => 'submission', 'submission_id' => $submission->id])
        ->and($submission->fresh()->owner_notified_at)->not->toBeNull();
});

it('asteapta cateva minute, ca prietenii veniti in rafala sa primeasca o singura notificare', function () {
    pendingFrom($this->profile, 'Ana Popescu');

    runPendingPush();

    expect($this->sender->sent)->toBeEmpty();
});

it('grupeaza mai multe completari intr-o singura notificare', function () {
    foreach (['Ana Popescu', 'Ana Lungu', 'Ana Botnaru'] as $name) {
        pendingFrom($this->profile, $name);
    }

    $this->travel(NotifyOwnersOfPendingSubmissions::GRACE_MINUTES + 1)->minutes();
    runPendingPush();
    runPendingPush();

    expect($this->sender->sent)->toHaveCount(1)
        ->and($this->sender->sent[0]->title)->toBe('3 completări așteaptă răspunsul tău')
        ->and($this->sender->sent[0]->data)->toBe(['type' => 'submissions'])
        ->and(ProfileSubmission::whereNull('owner_notified_at')->count())->toBe(0);
});

it('nu trimite in orele de liniste, ci dupa ele', function () {
    // 00:30 la Chișinău.
    $this->travelTo(CarbonImmutable::parse('2026-09-14 21:30', 'UTC'));
    pendingFrom($this->profile, 'Ana Popescu');

    $this->travel(NotifyOwnersOfPendingSubmissions::GRACE_MINUTES + 1)->minutes();
    runPendingPush();

    expect($this->sender->sent)->toBeEmpty();

    // 09:00 la Chișinău, după sfârșitul liniștii.
    $this->travelTo(CarbonImmutable::parse('2026-09-15 06:00', 'UTC'));
    runPendingPush();

    expect($this->sender->sent)->toHaveCount(1);
});

it('nu trimite cand proprietarul a oprit notificarile', function () {
    UserSettings::where('user_id', $this->owner->id)->update(['push_enabled' => false]);
    pendingFrom($this->profile, 'Ana Popescu');

    $this->travel(NotifyOwnersOfPendingSubmissions::GRACE_MINUTES + 1)->minutes();
    runPendingPush();

    expect($this->sender->sent)->toBeEmpty();
});

it('trimite cel mult o notificare pe zi', function () {
    pendingFrom($this->profile, 'Ana Popescu');
    $this->travel(NotifyOwnersOfPendingSubmissions::GRACE_MINUTES + 1)->minutes();
    runPendingPush();

    $this->travel(1)->hours();
    pendingFrom($this->profile, 'Ana Lungu');
    $this->travel(NotifyOwnersOfPendingSubmissions::GRACE_MINUTES + 1)->minutes();
    runPendingPush();

    expect($this->sender->sent)->toHaveCount(1);

    $this->travel(1)->days();
    runPendingPush();

    // A doua zi: amândouă așteaptă încă.
    expect($this->sender->sent)->toHaveCount(2)
        ->and($this->sender->sent[1]->title)->toBe('2 completări așteaptă răspunsul tău');
});

it('nu trimite daca proprietarul a raspuns deja', function () {
    $submission = pendingFrom($this->profile, 'Ana Popescu');

    $this->actingAs($this->owner)
        ->postJson("/api/v1/submissions/{$submission->id}/resolve", ['person_id' => null])
        ->assertOk();

    $this->travel(NotifyOwnersOfPendingSubmissions::GRACE_MINUTES + 1)->minutes();
    runPendingPush();

    expect($this->sender->sent)->toBeEmpty();
});

it('scrie in limba proprietarului', function () {
    $this->owner->update(['locale' => 'ru']);
    pendingFrom($this->profile, 'Ana Popescu');

    $this->travel(NotifyOwnersOfPendingSubmissions::GRACE_MINUTES + 1)->minutes();
    runPendingPush();

    expect($this->sender->sent[0]->title)->toBe('Ana Popescu: новая анкета');
});

it('imparte limita zilnica de push cu reminderele', function () {
    $occasion = Person::where('display_name', 'Ana Rusu')->sole()->occasions()->first();

    foreach ([7, 3] as $daysBefore) {
        QueuedNotification::create([
            'user_id'       => $this->owner->id, 'occasion_id' => $occasion->id, 'channel' => 'push',
            'days_before'   => $daysBefore, 'occasion_year' => 2026, 'locale' => 'ro',
            'scheduled_for' => now()->addHours(3),
        ]);
    }

    pendingFrom($this->profile, 'Ana Popescu');
    $this->travel(NotifyOwnersOfPendingSubmissions::GRACE_MINUTES + 1)->minutes();
    runPendingPush();

    expect($this->sender->sent)->toBeEmpty();
});

it('asteapta un telefon inregistrat, fara sa piarda notificarea', function () {
    DeviceToken::where('user_id', $this->owner->id)->delete();
    $submission = pendingFrom($this->profile, 'Ana Popescu');

    $this->travel(NotifyOwnersOfPendingSubmissions::GRACE_MINUTES + 1)->minutes();
    runPendingPush();

    expect($this->sender->sent)->toBeEmpty()
        ->and($submission->fresh()->owner_notified_at)->toBeNull();

    DeviceToken::create(['user_id' => $this->owner->id, 'token' => 'ExponentPushToken[nou]', 'platform' => 'ios']);
    runPendingPush();

    expect($this->sender->sent)->toHaveCount(1);
});
