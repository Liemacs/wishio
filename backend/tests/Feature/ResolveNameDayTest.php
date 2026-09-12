<?php

use App\Domain\Occasions\Actions\ResolveNameDay;
use Database\Seeders\NameDaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(NameDaySeeder::class);
    $this->resolve = app(ResolveNameDay::class);
});

it('gaseste onomastica din prenumele exact', function () {
    $match = $this->resolve->best('Gheorghe Munteanu');

    expect($match)->not->toBeNull()
        ->and($match->nameDay->code)->toBe('sf_gheorghe')
        ->and($match->nameDay->month)->toBe(4)
        ->and($match->nameDay->day)->toBe(23)
        ->and($match->confidence)->toBe(1.0);
});

it('gaseste onomastica in ciuda diacriticelor', function () {
    // Motivul pentru care colatia conteaza: docs/00 § D-007.
    expect($this->resolve->best('Ștefan')?->nameDay->code)->toBe('sf_stefan')
        ->and($this->resolve->best('Stefan')?->nameDay->code)->toBe('sf_stefan')
        ->and($this->resolve->best('Gheorghiță')?->nameDay->code)->toBe('sf_gheorghe');
});

it('gaseste onomastica din forma chirilica', function () {
    expect($this->resolve->best('Николай')?->nameDay->code)->toBe('sf_nicolae')
        ->and($this->resolve->best('Мария')?->nameDay->code)->toBe('sf_maria_mare')
        ->and($this->resolve->best('Иван')?->nameDay->code)->toBe('sf_ioan');
});

it('gaseste numele rusesti scrise cu litere latine', function () {
    // In Moldova agendele contin frecvent "Tanea", "Colea", "Misa" —
    // forme rusesti transcrise, nu chirilice. Fara ele pierzi jumatate din contacte.
    expect($this->resolve->best('Tanea')?->nameDay->code)->toBe('sf_tatiana')
        ->and($this->resolve->best('Colea')?->nameDay->code)->toBe('sf_nicolae')
        ->and($this->resolve->best('Mișa')?->nameDay->code)->toBe('sf_mihail_gavriil')
        ->and($this->resolve->best('Serioja')?->nameDay->code)->toBe('sf_serghie')
        ->and($this->resolve->best('Natașa')?->nameDay->code)->toBe('sf_adrian_natalia');
});

it('da incredere mai mica diminutivelor', function () {
    $exact     = $this->resolve->best('Nicolae');
    $diminutiv = $this->resolve->best('Nicușor');

    expect($exact->confidence)->toBeGreaterThan($diminutiv->confidence)
        ->and($diminutiv->isDiminutive)->toBeTrue()
        ->and($diminutiv->nameDay->code)->toBe('sf_nicolae');
});

it('foloseste calendarul corect', function () {
    // Sarbatorile fixe pe stil vechi cad la data pe stil nou + 13 zile.
    $nou   = $this->resolve->best('Gheorghe', calendar: 'orthodox_new');
    $vechi = $this->resolve->best('Gheorghe', calendar: 'orthodox_old');

    expect([$nou->nameDay->day, $nou->nameDay->month])->toBe([23, 4])
        ->and([$vechi->nameDay->day, $vechi->nameDay->month])->toBe([6, 5]);
});

it('scade increderea cand potrivirea e pe al doilea cuvant', function () {
    // "Popescu Ion" — ordine inversata, se intampla in agende.
    $direct  = $this->resolve->best('Ion Popescu');
    $inversat = $this->resolve->best('Popescu Ion');

    expect($direct->confidence)->toBeGreaterThan($inversat->confidence)
        ->and($inversat->nameDay->code)->toBe('sf_ioan');
});

it('returneaza toate potrivirile cand un nume are mai multe sarbatori', function () {
    // Maria apare la 15 august (principala) si 8 septembrie (secundara).
    $toate = $this->resolve->all('Maria');

    expect($toate)->toHaveCount(2)
        ->and($toate->first()->nameDay->code)->toBe('sf_maria_mare');
});

it('nu inventeaza onomastici', function () {
    expect($this->resolve->best('Mama'))->toBeNull()
        ->and($this->resolve->best('Xyzabc'))->toBeNull()
        ->and($this->resolve->best('Taxi Chisinau'))->toBeNull();
});

it('marcheaza toate onomasticile ca neverificate', function () {
    // Pana la confruntarea cu un calendar bisericesc nu trimitem push.
    expect(App\Domain\Occasions\Models\NameDay::where('is_verified', true)->count())->toBe(0);
});
