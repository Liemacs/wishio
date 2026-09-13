<?php

use App\Domain\Catalog\Models\Offer;
use App\Domain\Catalog\Models\OutboundClick;
use App\Domain\Metrics\Actions\BuildMetricsReport;
use App\Domain\Occasions\Models\Occasion;
use App\Domain\People\Enums\FieldSource;
use App\Domain\People\Models\Person;
use App\Domain\Recommendations\Models\RecommendationRun;
use App\Domain\Reminders\Models\DeviceToken;
use App\Domain\Reminders\Models\QueuedNotification;
use App\Domain\Reminders\Models\UserSettings;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\InterestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

/** Un utilizator cu `$people` persoane, fiecare cu zi de naștere și onomastică. */
function metricsUser(array $attributes, int $people): User
{
    $user = User::factory()->create($attributes);

    for ($i = 1; $i <= $people; $i++) {
        $person = Person::create(['user_id' => $user->id, 'display_name' => "Persoana {$i}"]);

        foreach ([['birthday', 10], ['name_day', 20]] as [$type, $day]) {
            Occasion::create([
                'user_id' => $user->id, 'person_id' => $person->id, 'type' => $type,
                'month'   => $i, 'day' => $day, 'source' => FieldSource::OwnerManual->value, 'confirmed_at' => now(),
            ]);
        }
    }

    return $user;
}

function usedToken(User $user, CarbonImmutable|Carbon $lastUsed): void
{
    $user->createToken('mobile')->accessToken->forceFill(['last_used_at' => $lastUsed])->save();
}

function funnelStep(array $funnel, string $key): array
{
    return collect($funnel)->firstWhere('key', $key);
}

beforeEach(function () {
    $this->seed([InterestSeeder::class, CatalogSeeder::class]);

    // Activ: 5 persoane, notificări, un reminder deschis din două, idei, un magazin deschis.
    $this->active = metricsUser([], 5);
    DeviceToken::create(['user_id' => $this->active->id, 'token' => 'ExponentPushToken[a]', 'platform' => 'ios']);
    usedToken($this->active, now());

    $occasion = Occasion::where('user_id', $this->active->id)->first();

    foreach ([now(), null] as $opened) {
        QueuedNotification::create([
            'user_id'       => $this->active->id, 'occasion_id' => $occasion->id, 'channel' => 'push',
            'days_before'   => $opened ? 1 : 3, 'occasion_year' => 2026, 'locale' => 'ro',
            'scheduled_for' => now()->subHour(), 'sent_at' => now()->subHour(), 'opened_at' => $opened,
        ]);
    }

    RecommendationRun::create(['user_id' => $this->active->id, 'person_id' => $occasion->person_id, 'locale' => 'ro', 'status' => 'ready']);

    $offer = Offer::first();
    OutboundClick::create([
        'user_id'   => $this->active->id, 'offer_id' => $offer->id, 'merchant_id' => $offer->merchant_id,
        'person_id' => $occasion->person_id, 'price' => $offer->price, 'context' => 'recommendation',
    ]);

    // S-a oprit după o persoană; ultima folosire acum 10 zile.
    $this->idle = metricsUser([], 1);
    usedToken($this->idle, now()->subDays(10));

    // Contul demo nu intră în cifre, oricât de plin ar fi.
    $internal = metricsUser(['email' => 'review@wishio.md'], 6);
    usedToken($internal, now());
});

it('calculeaza pasii palniei pentru cohorta, fara conturile interne', function () {
    $funnel = app(BuildMetricsReport::class)->funnel(CarbonImmutable::now()->subDays(7), CarbonImmutable::now());

    expect(funnelStep($funnel, 'accounts'))->toMatchArray(['count' => 2, 'of' => 2])
        ->and(funnelStep($funnel, 'people_5'))->toMatchArray(['count' => 1, 'of' => 2, 'target' => 0.60])
        ->and(funnelStep($funnel, 'occasions_8'))->toMatchArray(['count' => 1])
        ->and(funnelStep($funnel, 'push_enabled'))->toMatchArray(['count' => 1])
        ->and(funnelStep($funnel, 'reminder_sent'))->toMatchArray(['count' => 1])
        // Deschiderile se raportează la reminderele trimise, nu la conturi.
        ->and(funnelStep($funnel, 'reminder_opened'))->toMatchArray(['count' => 1, 'of' => 2])
        ->and(funnelStep($funnel, 'recommendations'))->toMatchArray(['count' => 1])
        ->and(funnelStep($funnel, 'clicks'))->toMatchArray(['count' => 1])
        ->and(funnelStep($funnel, 'gift_given'))->toMatchArray(['count' => 0]);
});

it('nu numara un utilizator cu notificarile oprite din aplicatie', function () {
    UserSettings::create(['user_id' => $this->active->id, 'push_enabled' => false]);

    $funnel = app(BuildMetricsReport::class)->funnel(CarbonImmutable::now()->subDays(7), CarbonImmutable::now());

    expect(funnelStep($funnel, 'push_enabled')['count'])->toBe(0);
});

it('calculeaza cele cinci cifre ale saptamanii', function () {
    // Cohorta de acum 30–60 de zile: unul revine după ziua 30 (dar nu în ultima
    // săptămână, ca să nu fie numărat și activ), celălalt nu.
    usedToken(User::factory()->create(['created_at' => now()->subDays(40)]), now()->subDays(8));
    usedToken(User::factory()->create(['created_at' => now()->subDays(45)]), now()->subDays(40));

    $weekly = app(BuildMetricsReport::class)->weekly(CarbonImmutable::now());

    expect($weekly)->toMatchArray([
        'active_users'     => 1,
        'people_acted_for' => 1,
        'clicks'           => 1,
        'link_submissions' => 0,
        'retention_cohort' => 2,
        'retention_d30'    => 1,
    ]);
});

it('afiseaza raportul din linia de comanda', function () {
    $this->artisan('wishio:metrics', ['--since' => now()->subDays(7)->toDateString()])
        ->expectsOutputToContain('Conturile @wishio.md nu intră')
        ->assertSuccessful();
});
