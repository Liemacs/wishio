<?php

use App\Domain\Occasions\Models\Occasion;
use App\Domain\People\Models\GiftHistory;
use App\Domain\People\Models\GiftIdea;
use App\Domain\People\Models\Person;
use App\Domain\Recommendations\Models\RecommendationRun;
use App\Models\User;
use App\Support\Names\NameNormalizer;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\HolidaySeeder;
use Database\Seeders\InterestSeeder;
use Database\Seeders\NameDaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

const DEMO_SEEDERS = [NameDaySeeder::class, InterestSeeder::class, HolidaySeeder::class, CatalogSeeder::class];

it('creeaza contul demo cu date populate', function () {
    $this->seed(DEMO_SEEDERS);

    $this->artisan('wishio:demo', ['--password' => 'parola-demo-123'])->assertSuccessful();

    $user = User::where('email', 'review@wishio.md')->sole();

    expect(Hash::check('parola-demo-123', $user->password))->toBeTrue()
        ->and($user->locale)->toBe('en')
        ->and($user->people()->count())->toBeGreaterThanOrEqual(8)
        ->and(Occasion::where('user_id', $user->id)->where('type', 'name_day')->whereNotNull('confirmed_at')->count())->toBeGreaterThan(0)
        ->and(GiftIdea::where('user_id', $user->id)->count())->toBeGreaterThan(0)
        ->and(GiftHistory::where('user_id', $user->id)->count())->toBeGreaterThan(0)
        ->and(RecommendationRun::where('user_id', $user->id)->where('status', 'ready')->count())->toBe(1)
        ->and($user->publicProfile->is_active)->toBeTrue()
        ->and($user->wishlistItems()->count())->toBeGreaterThan(0)
        // „Cine este?”: o completare care așteaptă decizia proprietarului.
        ->and($user->publicProfile->submissions()->whereNull('accepted_at')->count())->toBe(1);
});

it('are mereu o ocazie in urmatoarele 30 de zile', function () {
    // Cine deschide contul în App Review trebuie să vadă imediat ce face aplicația.
    $this->seed(DEMO_SEEDERS);

    $this->artisan('wishio:demo', ['--password' => 'parola-demo-123'])->assertSuccessful();

    $occasions = Occasion::where('user_id', User::where('email', 'review@wishio.md')->value('id'))->get();

    expect($occasions->contains(fn (Occasion $occasion) => $occasion->daysUntil() <= 30))->toBeTrue();
});

it('reface contul la a doua rulare, fara dubluri', function () {
    $this->seed(DEMO_SEEDERS);

    $this->artisan('wishio:demo', ['--password' => 'parola-demo-123'])->assertSuccessful();
    $people = User::where('email', 'review@wishio.md')->sole()->people()->count();

    $this->artisan('wishio:demo', ['--password' => 'alta-parola-456', '--force' => true])->assertSuccessful();
    $user = User::where('email', 'review@wishio.md')->sole();

    expect($user->people()->count())->toBe($people)
        ->and(Hash::check('alta-parola-456', $user->password))->toBeTrue();
});

it('nu sterge un cont existent fara confirmare', function () {
    $this->seed(DEMO_SEEDERS);

    $existing = User::factory()->create(['email' => 'review@wishio.md']);
    Person::create(['user_id' => $existing->id, 'display_name' => 'Nu mă șterge']);

    $this->artisan('wishio:demo', ['--password' => 'parola-demo-123'])
        ->expectsConfirmation('Contul review@wishio.md există. Îl ștergi și îl refaci cu date demo?', 'no')
        ->assertFailed();

    expect(Person::where('display_name', 'Nu mă șterge')->exists())->toBeTrue();
});

it('nu atinge alti utilizatori', function () {
    $this->seed(DEMO_SEEDERS);

    $other = User::factory()->create();
    Person::create(['user_id' => $other->id, 'display_name' => 'Al altcuiva']);

    $this->artisan('wishio:demo', ['--password' => 'parola-demo-123'])->assertSuccessful();
    $this->artisan('wishio:demo', ['--password' => 'parola-demo-123', '--force' => true])->assertSuccessful();

    expect($other->people()->pluck('display_name')->all())->toBe(['Al altcuiva']);
});

it('foloseste limba ceruta, cu nume si calendar potrivite', function () {
    $this->seed(DEMO_SEEDERS);

    $this->artisan('wishio:demo', [
        '--password' => 'parola-demo-123', '--locale' => 'ru', '--email' => 'demo-ru@wishio.md',
    ])->assertSuccessful();

    $user = User::where('email', 'demo-ru@wishio.md')->sole();

    // Vorbitorii de rusă din Moldova urmează în general stilul vechi, ca la înregistrare.
    expect($user->locale)->toBe('ru')
        ->and($user->name_day_calendar)->toBe('orthodox_old')
        ->and(app(NameNormalizer::class)->isCyrillic($user->people()->first()->display_name))->toBeTrue();
});

it('genereaza o parola cand nu primeste una', function () {
    $this->seed(DEMO_SEEDERS);
    config(['wishio.demo.password' => null]);

    $this->artisan('wishio:demo')
        ->expectsOutputToContain('generată')
        ->assertSuccessful();

    expect(User::where('email', 'review@wishio.md')->exists())->toBeTrue();
});

it('refuza fara datele de baza sau cu o limba nesuportata', function () {
    $this->artisan('wishio:demo', ['--password' => 'parola-demo-123'])->assertFailed();

    $this->seed(DEMO_SEEDERS);
    $this->artisan('wishio:demo', ['--password' => 'parola-demo-123', '--locale' => 'de'])->assertFailed();

    expect(User::where('email', 'review@wishio.md')->exists())->toBeFalse();
});
