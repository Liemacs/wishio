<?php

use App\Domain\Occasions\Actions\SyncHolidayOccasions;
use App\Domain\Occasions\Models\Holiday;
use App\Domain\Occasions\Models\Occasion;
use App\Domain\People\Models\Person;
use App\Domain\Reminders\Actions\BuildReminderContent;
use App\Models\User;
use App\Support\Calendar\OrthodoxEaster;
use Carbon\CarbonImmutable;
use Database\Seeders\HolidaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(HolidaySeeder::class);
    $this->user = User::factory()->create(['country_code' => 'MD', 'timezone' => 'Europe/Chisinau', 'locale' => 'ro']);
    $this->sync = app(SyncHolidayOccasions::class);
});

it('calculeaza corect Pastele ortodox', function (int $year, string $expected) {
    // Verificat pe ani cunoscuti — o eroare aici muta cea mai mare sarbatoare.
    expect(OrthodoxEaster::for($year)->format('Y-m-d'))->toBe($expected);
})->with([
    [2024, '2024-05-05'],
    [2025, '2025-04-20'],
    [2026, '2026-04-12'],
    [2027, '2027-05-02'],
]);

it('materializeaza sarbatorile apropiate', function () {
    // 1 martie: 8 Martie e peste o saptamana, Pastele peste ~6 saptamani.
    ($this->sync)($this->user, CarbonImmutable::parse('2026-03-01 09:00', 'Europe/Chisinau'));

    $codes = Occasion::where('type', 'holiday')->with('holiday')->get()->pluck('holiday.code');

    expect($codes)->toContain('march_8')
        ->and(Occasion::where('type', 'holiday')->first()->person_id)->toBeNull();
});

it('nu materializeaza sarbatori departate', function () {
    // In ianuarie, 1 Iunie e la 5 luni distanta.
    ($this->sync)($this->user, CarbonImmutable::parse('2026-01-20 09:00', 'Europe/Chisinau'));

    $codes = Occasion::where('type', 'holiday')->with('holiday')->get()->pluck('holiday.code');

    expect($codes)->not->toContain('june_1');
});

it('foloseste data mobila corecta pentru Paste', function () {
    ($this->sync)($this->user, CarbonImmutable::parse('2026-04-01 09:00', 'Europe/Chisinau'));

    $easter = Occasion::whereHas('holiday', fn ($q) => $q->where('code', 'easter'))->sole();

    expect([$easter->day, $easter->month, $easter->year])->toBe([12, 4, 2026]);
});

it('nu creeaza duplicate la rulari repetate', function () {
    $now = CarbonImmutable::parse('2026-03-01 09:00', 'Europe/Chisinau');

    foreach (range(1, 3) as $n) {
        ($this->sync)($this->user, $now);
    }

    expect(Occasion::where('type', 'holiday')->count())
        ->toBe(Occasion::where('type', 'holiday')->distinct('holiday_id')->count('holiday_id'));
});

it('selecteaza publicul potrivit fiecarei sarbatori', function () {
    $women = Person::create(['user_id' => $this->user->id, 'display_name' => 'Ana', 'gender' => 'f']);
    $men   = Person::create(['user_id' => $this->user->id, 'display_name' => 'Ion', 'gender' => 'm']);
    $child = Person::create(['user_id' => $this->user->id, 'display_name' => 'Luca', 'relationship' => 'child']);

    ($this->sync)($this->user, CarbonImmutable::parse('2026-03-01 09:00', 'Europe/Chisinau'));

    $march8 = Occasion::whereHas('holiday', fn ($q) => $q->where('code', 'march_8'))->sole();

    expect($march8->audience()->pluck('id')->all())->toBe([$women->id])
        ->and($march8->audience()->pluck('id')->all())->not->toContain($men->id, $child->id);
});

it('include copiii dupa relatie, nu doar dupa varsta', function () {
    // Varsta lipseste foarte des; relatia e semnalul de incredere.
    $child = Person::create(['user_id' => $this->user->id, 'display_name' => 'Luca', 'relationship' => 'child']);
    Person::create(['user_id' => $this->user->id, 'display_name' => 'Ana', 'gender' => 'f']);

    ($this->sync)($this->user, CarbonImmutable::parse('2026-05-20 09:00', 'Europe/Chisinau'));

    $june1 = Occasion::whereHas('holiday', fn ($q) => $q->where('code', 'june_1'))->sole();

    expect($june1->audience()->pluck('id')->all())->toBe([$child->id]);
});

it('produce o singura notificare pe sarbatoare, nu una per persoana', function () {
    // Douasprezece notificari de 8 Martie ar fi zgomot garantat.
    foreach (range(1, 12) as $n) {
        Person::create(['user_id' => $this->user->id, 'display_name' => "Persoana $n", 'gender' => 'f']);
    }

    ($this->sync)($this->user, CarbonImmutable::parse('2026-03-01 09:00', 'Europe/Chisinau'));

    $march8 = Occasion::whereHas('holiday', fn ($q) => $q->where('code', 'march_8'))->sole();
    $content = app(BuildReminderContent::class)($march8->load('holiday'), 3, 'ro');

    expect($content['title'])->toContain('Ziua Femeii')->toContain('peste 3 zile')
        ->and($content['body'])->toContain('12 persoane');
});

it('traduce sarbatorile', function (string $locale, string $expected) {
    $holiday = Holiday::where('code', 'march_8')->sole();

    expect($holiday->label($locale))->toBe($expected);
})->with([
    ['ro', 'Ziua Femeii'],
    ['ru', 'Международный женский день'],
    ['en', "International Women's Day"],
]);

it('expune sarbatorile si publicul lor prin API', function () {
    Person::create(['user_id' => $this->user->id, 'display_name' => 'Ana', 'gender' => 'f']);
    ($this->sync)($this->user, CarbonImmutable::parse('2026-03-01 09:00', 'Europe/Chisinau'));

    $response = $this->actingAs($this->user)->getJson('/api/v1/occasions')->assertOk();

    $march8 = collect($response->json('data'))->firstWhere('type', 'holiday');

    expect($march8['label'])->not->toBeEmpty()
        ->and($march8['person'] ?? null)->toBeNull()
        ->and($march8)->toHaveKey('audience');
});

it('trece la anul urmator dupa ce sarbatoarea a trecut', function () {
    // 10 martie: 8 Martie a trecut, urmatoarea e in 2027.
    $march8 = Holiday::where('code', 'march_8')->sole();
    $next = $march8->nextDate(CarbonImmutable::parse('2026-03-10', 'Europe/Chisinau'));

    expect($next->format('Y-m-d'))->toBe('2027-03-08');
});
