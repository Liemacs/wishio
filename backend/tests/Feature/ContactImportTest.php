<?php

use App\Domain\Occasions\Models\Occasion;
use App\Domain\People\Enums\FieldSource;
use App\Domain\People\Models\Person;
use App\Models\User;
use Database\Seeders\NameDaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(NameDaySeeder::class);
    $this->user = User::factory()->create(['country_code' => 'MD', 'name_day_calendar' => 'orthodox_new']);
});

function contact(string $id, string $name, ?string $birthday = null, bool $yearKnown = false): array
{
    return array_filter([
        'device_contact_id' => $id,
        'display_name'      => $name,
        'birth_date'        => $birthday,
        'birth_year_known'  => $yearKnown,
    ], fn ($v) => $v !== null);
}

it('importa contactele alese si raporteaza ce a gasit', function () {
    $response = $this->actingAs($this->user)->postJson('/api/v1/contacts/import', [
        'contacts' => [
            contact('c1', 'Gheorghe Rusu', '1990-04-10', true),
            contact('c2', 'Maria Ciobanu'),
            contact('c3', 'Николай Петров'),
        ],
    ])->assertCreated();

    expect($response->json('data.created'))->toBe(3)
        ->and($response->json('data.birthdays'))->toBe(1)
        // Trei prenume, trei onomastici — asta e valoarea mecanismului.
        ->and($response->json('data.name_days'))->toBe(3)
        ->and($response->json('data.name_days_to_confirm'))->toBe(3);
});

it('deduce onomastica din prenume, chiar fara zi de nastere', function () {
    $this->actingAs($this->user)->postJson('/api/v1/contacts/import', [
        'contacts' => [contact('c1', 'Gheorghe Rusu')],
    ]);

    $occasion = Occasion::where('type', 'name_day')->sole();

    expect([$occasion->day, $occasion->month])->toBe([23, 4])
        ->and($occasion->source)->toBe(FieldSource::Derived)
        ->and($occasion->confirmed_at)->toBeNull();
});

it('foloseste calendarul utilizatorului', function () {
    // Acelasi prenume, doua calendare, doua date.
    $this->user->update(['name_day_calendar' => 'orthodox_old']);

    $this->actingAs($this->user)->postJson('/api/v1/contacts/import', [
        'contacts' => [contact('c1', 'Gheorghe')],
    ]);

    $occasion = Occasion::where('type', 'name_day')->sole();

    expect([$occasion->day, $occasion->month])->toBe([6, 5]);
});

it('nu genereaza notificari pentru onomastici neverificate', function () {
    // Datele nu au fost inca confruntate cu un calendar bisericesc — docs/15 § 4.
    $this->actingAs($this->user)->postJson('/api/v1/contacts/import', [
        'contacts' => [contact('c1', 'Gheorghe')],
    ]);

    expect(Occasion::where('type', 'name_day')->sole()->mayNotify())->toBeFalse();
});

it('creeaza ziua de nastere ca ocazie deja confirmata', function () {
    // O zi venita din agenda nu e o presupunere, deci nu cere confirmare.
    $this->actingAs($this->user)->postJson('/api/v1/contacts/import', [
        'contacts' => [contact('c1', 'Ana', '1992-11-03', true)],
    ]);

    $occasion = Occasion::where('type', 'birthday')->sole();

    expect([$occasion->day, $occasion->month])->toBe([3, 11])
        ->and($occasion->year)->toBe(1992)
        ->and($occasion->confirmed_at)->not->toBeNull()
        ->and($occasion->mayNotify())->toBeTrue();
});

it('nu stocheaza numere de telefon', function () {
    // docs/00 § D-017: MVP-ul nu colecteaza deloc numere.
    $this->actingAs($this->user)->postJson('/api/v1/contacts/import', [
        'contacts' => [contact('c1', 'Ana') + ['phone' => '+37369123456']],
    ]);

    expect(Person::sole()->contact_hash)->toBeNull()
        ->and(Person::sole()->getAttributes())->not->toHaveKey('phone');
});

it('nu suprascrie ce a modificat utilizatorul manual', function () {
    // Scenariul din docs/04: ai salvat-o ca „Daniela ❤️”. Re-sincronizarea
    // agendei nu are voie sa revina peste.
    $this->actingAs($this->user)->postJson('/api/v1/contacts/import', [
        'contacts' => [contact('c1', 'Daniela Casianov')],
    ]);

    $person = Person::sole();
    $this->actingAs($this->user)->patchJson("/api/v1/people/{$person->id}", ['display_name' => 'Daniela ❤️']);

    $this->actingAs($this->user)->postJson('/api/v1/contacts/import', [
        'contacts' => [contact('c1', 'Daniela Casianov')],
    ]);

    expect(Person::sole()->display_name)->toBe('Daniela ❤️');
});

it('nu creeaza duplicate la re-import', function () {
    foreach (range(1, 3) as $n) {
        $this->actingAs($this->user)->postJson('/api/v1/contacts/import', [
            'contacts' => [contact('c1', 'Ana', '1992-11-03', true)],
        ]);
    }

    expect(Person::count())->toBe(1)
        ->and(Occasion::where('type', 'birthday')->count())->toBe(1);
});

it('actualizeaza ziua de nastere cand se schimba in agenda', function () {
    $this->actingAs($this->user)->postJson('/api/v1/contacts/import', [
        'contacts' => [contact('c1', 'Ana', '1992-11-03', true)],
    ]);

    $this->actingAs($this->user)->postJson('/api/v1/contacts/import', [
        'contacts' => [contact('c1', 'Ana', '1992-12-25', true)],
    ]);

    $occasions = Occasion::where('type', 'birthday')->get();

    expect($occasions)->toHaveCount(1)
        ->and([$occasions->first()->day, $occasions->first()->month])->toBe([25, 12]);
});

it('ignora contactele care nu sunt persoane', function () {
    $this->actingAs($this->user)->postJson('/api/v1/contacts/import', [
        'contacts' => [contact('c1', 'Taxi Chisinau'), contact('c2', 'Mama')],
    ]);

    // Se creeaza persoanele — utilizatorul le-a ales — dar fara onomastici.
    expect(Person::count())->toBe(2)
        ->and(Occasion::where('type', 'name_day')->count())->toBe(0);
});

it('confirma o onomastica si o promoveaza la manual', function () {
    $this->actingAs($this->user)->postJson('/api/v1/contacts/import', [
        'contacts' => [contact('c1', 'Gheorghe')],
    ]);

    $occasion = Occasion::where('type', 'name_day')->sole();

    $this->actingAs($this->user)
        ->patchJson("/api/v1/occasions/{$occasion->id}", ['status' => 'confirmed'])
        ->assertOk()
        ->assertJsonPath('data.confirmed', true)
        ->assertJsonPath('data.may_notify', true);

    expect($occasion->fresh()->source)->toBe(FieldSource::OwnerManual);
});

it('permite corectarea datei la confirmare', function () {
    // „Da, are onomastica, dar pe 6 mai” — cazul celor doua calendare.
    $this->actingAs($this->user)->postJson('/api/v1/contacts/import', [
        'contacts' => [contact('c1', 'Gheorghe')],
    ]);

    $occasion = Occasion::where('type', 'name_day')->sole();

    $this->actingAs($this->user)->patchJson("/api/v1/occasions/{$occasion->id}", [
        'status' => 'confirmed', 'month' => 5, 'day' => 6,
    ])->assertOk();

    expect([$occasion->fresh()->day, $occasion->fresh()->month])->toBe([6, 5]);
});

it('nu re-propune o onomastica respinsa', function () {
    $this->actingAs($this->user)->postJson('/api/v1/contacts/import', [
        'contacts' => [contact('c1', 'Gheorghe')],
    ]);

    $occasion = Occasion::where('type', 'name_day')->sole();
    $this->actingAs($this->user)->patchJson("/api/v1/occasions/{$occasion->id}", ['status' => 'rejected']);

    $this->actingAs($this->user)->postJson('/api/v1/contacts/import', [
        'contacts' => [contact('c1', 'Gheorghe')],
    ]);

    $after = Occasion::where('type', 'name_day')->sole();

    expect($after->rejected_at)->not->toBeNull()
        ->and($after->mayNotify())->toBeFalse();
});

it('nu lasa un utilizator sa atinga ocaziile altuia', function () {
    $other = User::factory()->create();
    $person = Person::create(['user_id' => $other->id, 'display_name' => 'Ana']);
    $occasion = Occasion::create([
        'user_id' => $other->id, 'person_id' => $person->id, 'type' => 'birthday',
        'month'   => 1, 'day' => 1, 'source' => FieldSource::OwnerManual->value,
    ]);

    $this->actingAs($this->user)
        ->patchJson("/api/v1/occasions/{$occasion->id}", ['status' => 'confirmed'])
        ->assertForbidden();
});

it('listeaza ocaziile in ordinea apropierii', function () {
    $this->actingAs($this->user)->postJson('/api/v1/contacts/import', [
        'contacts' => [
            contact('c1', 'Ana', '1992-'.now()->addDays(40)->format('m-d'), true),
            contact('c2', 'Ion', '1992-'.now()->addDays(5)->format('m-d'), true),
        ],
    ]);

    $days = collect($this->actingAs($this->user)->getJson('/api/v1/occasions')->json('data'))
        ->pluck('days_until');

    expect($days->toArray())->toBe($days->sort()->values()->toArray());
});
