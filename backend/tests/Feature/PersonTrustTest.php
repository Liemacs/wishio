<?php

use App\Domain\People\Actions\WritePersonField;
use App\Domain\People\Enums\FieldSource;
use App\Domain\People\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->person = Person::create([
        'user_id'      => $this->user->id,
        'display_name' => 'Ana',
    ]);
    $this->write = app(WritePersonField::class);
});

it('accepta prima scriere, indiferent de sursa', function () {
    expect(($this->write)($this->person, 'birth_date', '1995-04-23', FieldSource::AiInferred))->toBeTrue()
        ->and($this->person->fresh()->birth_date->format('Y-m-d'))->toBe('1995-04-23');
});

it('lasa o sursa mai de incredere sa suprascrie una mai slaba', function () {
    ($this->write)($this->person, 'birth_date', '1995-04-23', FieldSource::Derived);
    ($this->write)($this->person, 'birth_date', '1995-05-01', FieldSource::DeviceContact);

    expect($this->person->fresh()->birth_date->format('Y-m-d'))->toBe('1995-05-01')
        ->and($this->person->fresh()->trustLevel('birth_date'))->toBe(FieldSource::DeviceContact);
});

it('respinge o sursa mai slaba decat cea existenta', function () {
    ($this->write)($this->person, 'birth_date', '1995-04-23', FieldSource::OwnerManual);

    // Sincronizarea agendei nu are voie sa strice ce a scris omul.
    expect(($this->write)($this->person, 'birth_date', '1990-01-01', FieldSource::DeviceContact))->toBeFalse()
        ->and($this->person->fresh()->birth_date->format('Y-m-d'))->toBe('1995-04-23');
});

it('lasa proprietarul sa editeze chiar si ce a completat persoana insasi', function () {
    // Ierarhia guverneaza scrierile AUTOMATE, nu actiunile omului. Contactul
    // e al lui: daca vrea „Ana ❤️”, asta ramane.
    ($this->write)($this->person, 'display_name', 'Ana Casianov', FieldSource::SubjectProvided);

    expect(($this->write)($this->person, 'display_name', 'Ana ❤️', FieldSource::OwnerManual))->toBeTrue()
        ->and($this->person->fresh()->display_name)->toBe('Ana ❤️')
        ->and($this->person->fresh()->isOverridden('display_name'))->toBeTrue();
});

it('lasa persoana insasi sa corecteze datele deduse', function () {
    // Ierarhia: subject_confirmed bate tot ce nu e modificat manual.
    ($this->write)($this->person, 'birth_date', '1995-04-23', FieldSource::DeviceContact);
    ($this->write)($this->person, 'birth_date', '1995-10-12', FieldSource::SubjectConfirmed);

    expect($this->person->fresh()->birth_date->format('Y-m-d'))->toBe('1995-10-12');
});

it('protejeaza definitiv un camp modificat manual', function () {
    // Scenariul din docs/04: ai salvat-o ca „Daniela ❤️”. Nicio sincronizare
    // — nici macar claim-ul persoanei — nu are voie sa revina peste.
    ($this->write)($this->person, 'display_name', 'Daniela ❤️', FieldSource::OwnerManual);

    expect($this->person->isOverridden('display_name'))->toBeTrue();

    foreach ([FieldSource::DeviceContact, FieldSource::Derived, FieldSource::AiInferred,
        FieldSource::SubjectProvided, FieldSource::SubjectConfirmed] as $source) {
        expect(($this->write)($this->person, 'display_name', 'Daniela Casianov', $source))->toBeFalse();
    }

    expect($this->person->fresh()->display_name)->toBe('Daniela ❤️');
});

it('lasa proprietarul sa-si schimbe propria modificare', function () {
    ($this->write)($this->person, 'display_name', 'Daniela ❤️', FieldSource::OwnerManual);

    expect(($this->write)($this->person, 'display_name', 'Dani', FieldSource::OwnerManual))->toBeTrue()
        ->and($this->person->fresh()->display_name)->toBe('Dani');
});

it('nu ridica marcajul de modificare manuala', function () {
    ($this->write)($this->person, 'display_name', 'Dani', FieldSource::OwnerManual);
    $first = $this->person->fresh()->sourceFor('display_name')->overridden_at;

    ($this->write)($this->person, 'display_name', 'Daniela', FieldSource::OwnerManual);

    expect($this->person->fresh()->sourceFor('display_name')->overridden_at->timestamp)
        ->toBe($first->timestamp);
});

it('inregistreaza increderea implicita a fiecarei surse', function () {
    ($this->write)($this->person, 'gender', 'f', FieldSource::AiInferred);

    expect($this->person->fresh()->sourceFor('gender')->confidence)->toBe(0.50);
});

it('refuza campurile fara provenienta urmarita', function () {
    ($this->write)($this->person, 'notes', 'ceva', FieldSource::OwnerManual);
})->throws(InvalidArgumentException::class);

it('cripteaza notele la rest', function () {
    // Notele pot contine date sensibile; nu trebuie sa fie lizibile in baza.
    $this->person->update(['notes' => 'Are diabet, fără dulciuri.']);

    $raw = DB::table('people')->where('id', $this->person->id)->value('notes');

    expect($raw)->not->toContain('diabet')
        ->and($this->person->fresh()->notes)->toBe('Are diabet, fără dulciuri.');
});

it('calculeaza varsta doar cand stim anul', function () {
    // Din agenda vine deseori doar ziua si luna.
    $this->person->update(['birth_date' => '1995-04-23', 'birth_year_known' => false]);
    expect($this->person->fresh()->age())->toBeNull();

    $this->person->update(['birth_year_known' => true]);
    expect($this->person->fresh()->age())->toBeInt();
});
