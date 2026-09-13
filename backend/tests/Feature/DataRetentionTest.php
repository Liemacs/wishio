<?php

use App\Domain\Catalog\Jobs\AggregateOldClicks;
use App\Domain\Catalog\Models\Offer;
use App\Domain\Catalog\Models\OutboundClick;
use App\Domain\Occasions\Models\Occasion;
use App\Domain\People\Enums\FieldSource;
use App\Domain\People\Models\Person;
use App\Domain\Reminders\Models\QueuedNotification;
use App\Models\PersonalAccessToken;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\InterestSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Termenele promise în politică și în docs/21 (M-08, M-11) se aplică singure,
 * în fiecare noapte: nimeni nu trebuie să-și amintească să le ruleze.
 */

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

function retentionReminder(User $user, DateTimeInterface $scheduledFor): QueuedNotification
{
    $person = Person::create(['user_id' => $user->id, 'display_name' => 'Alex']);

    $occasion = Occasion::create([
        'user_id' => $user->id, 'person_id' => $person->id, 'type' => 'birthday',
        'month'   => 12, 'day' => 25, 'source' => FieldSource::OwnerManual->value, 'confirmed_at' => now(),
    ]);

    return QueuedNotification::create([
        'user_id'       => $user->id, 'occasion_id' => $occasion->id, 'channel' => 'push',
        'days_before'   => 1, 'occasion_year' => (int) $scheduledFor->format('Y'), 'locale' => 'ro',
        'scheduled_for' => $scheduledFor, 'sent_at' => $scheduledFor,
    ]);
}

function retentionToken(User $user, ?DateTimeInterface $lastUsedAt, DateTimeInterface $createdAt): string
{
    $token = $user->createToken('mobile');
    $token->accessToken->forceFill(['last_used_at' => $lastUsedAt, 'created_at' => $createdAt])->save();

    return $token->plainTextToken;
}

function retentionClick(User $user, Offer $offer, DateTimeInterface $at): OutboundClick
{
    return OutboundClick::create([
        'user_id' => $user->id, 'offer_id' => $offer->id, 'merchant_id' => $offer->merchant_id,
        'price'   => $offer->price, 'context' => 'recommendation', 'created_at' => $at, 'updated_at' => $at,
    ]);
}

it('sterge istoricul reminderelor mai vechi de 13 luni', function () {
    retentionReminder($this->user, now()->subMonths(13)->subDay());
    $recent = retentionReminder($this->user, now()->subMonths(12));

    $this->artisan('model:prune', ['--model' => [QueuedNotification::class]])->assertSuccessful();

    expect(QueuedNotification::pluck('id')->all())->toBe([$recent->id]);
});

it('inchide sesiunile in care aplicatia nu s-a mai deschis de 12 luni', function () {
    $idle = retentionToken($this->user, now()->subMonths(12)->subDay(), now()->subYears(2));
    retentionToken($this->user, null, now()->subMonths(12)->subDay());   // creat și nefolosit niciodată
    $active = retentionToken($this->user, now()->subDay(), now()->subYears(2));
    retentionToken($this->user, null, now()->subDay());                  // abia creat

    $this->artisan('model:prune', ['--model' => [PersonalAccessToken::class]])->assertSuccessful();

    expect($this->user->tokens()->count())->toBe(2);

    // Cu un token șters, aplicația primește 401 și cere autentificarea din nou.
    $this->withToken($idle)->getJson('/api/v1/people')->assertUnauthorized();

    $this->app['auth']->forgetGuards();

    $this->withToken($active)->getJson('/api/v1/people')->assertOk();
});

it('pastreaza clickurile 24 de luni, apoi doar totalul pe luna, comerciant si oferta', function () {
    $this->seed(InterestSeeder::class);
    $this->seed(CatalogSeeder::class);

    [$first, $second] = Offer::orderBy('id')->take(2)->get()->all();
    $old = now()->subMonths(26)->startOfMonth()->addDays(10);

    retentionClick($this->user, $first, $old);
    retentionClick($this->user, $first, $old->copy()->addDays(5));
    retentionClick($this->user, $second, $old);
    $recent = retentionClick($this->user, $first, now()->subMonths(23));

    expect((new AggregateOldClicks)->handle())->toBe(3);

    $stats = fn () => DB::table('click_stats')->orderBy('offer_id')->get()
        ->map(fn ($row) => [$row->month, (int) $row->merchant_id, (int) $row->offer_id, (int) $row->total])
        ->all();

    $month = $old->format('Y-m-01');

    expect(OutboundClick::pluck('id')->all())->toBe([$recent->id])
        ->and($stats())->toBe([
            [$month, $first->merchant_id, $first->id, 2],
            [$month, $second->merchant_id, $second->id, 1],
        ]);

    // Alt click din aceeași lună trece pragul mai târziu: totalul crește, nu se dublează.
    retentionClick($this->user, $first, $old->copy()->addDays(12));
    (new AggregateOldClicks)->handle();

    expect($stats()[0][3])->toBe(3)
        ->and(OutboundClick::count())->toBe(1)
        // Agregatul nu mai ține de niciun om: nici utilizator, nici persoană, nici preț.
        ->and(Schema::getColumnListing('click_stats'))->not->toContain('user_id')
        ->not->toContain('person_id')
        ->not->toContain('price');
});

it('ruleaza curatenia in fiecare noapte, fara interventie', function () {
    $events = collect(app(Schedule::class)->events());

    $prune = $events->first(fn ($event) => str_contains((string) $event->command, 'model:prune'));

    expect($prune)->not->toBeNull()
        ->and($prune->command)->toContain('QueuedNotification')->toContain('PersonalAccessToken')
        ->and($events->contains(fn ($event) => $event->description === AggregateOldClicks::class))->toBeTrue()
        ->and($events->contains(fn ($event) => str_contains((string) $event->command, 'queue:prune-failed')))->toBeTrue();
});
