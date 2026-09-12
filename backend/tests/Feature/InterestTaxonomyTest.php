<?php

use App\Domain\People\Actions\MatchInterestsFromText;
use App\Domain\People\Models\Interest;
use App\Domain\People\Models\InterestGroup;
use Database\Seeders\InterestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(InterestSeeder::class);
    $this->match = app(MatchInterestsFromText::class);
});

it('incarca taxonomia completa', function () {
    expect(InterestGroup::count())->toBe(15)
        ->and(Interest::count())->toBe(80)
        ->and(Interest::where('is_experience', true)->count())->toBe(9);
});

it('are traduceri in toate cele trei limbi pentru fiecare intrare', function () {
    // Regula 1 din CLAUDE.md: nimic nu e gata fara RO, RU si EN.
    $incomplete = Interest::all()
        ->filter(fn (Interest $i) => count(array_filter($i->translations)) !== 3)
        ->pluck('code');

    expect($incomplete)->toBeEmpty();

    $groups = InterestGroup::all()
        ->filter(fn (InterestGroup $g) => count(array_filter($g->translations)) !== 3)
        ->pluck('code');

    expect($groups)->toBeEmpty();
});

it('are cuvinte-cheie in toate cele trei limbi', function () {
    $missing = Interest::all()
        ->filter(fn (Interest $i) => count(array_filter($i->keywords)) !== 3)
        ->pluck('code');

    expect($missing)->toBeEmpty();
});

it('expune contractul de coduri valide pentru AI', function () {
    // AI-ul poate returna DOAR aceste coduri; orice altceva se arunca.
    $codes = Interest::validCodes();

    expect($codes)->toHaveCount(80)
        ->and($codes)->toContain('audio', 'fitness_gym', 'fragrance_women', 'karting_exp')
        ->and($codes)->not->toContain('inventat_de_ai');
});

it('deduce interese din text liber in romana', function () {
    $result = ($this->match)('Alex lucrează ca programator, îi plac mașinile și merge la sală');
    $codes = $result->pluck('interest.code');

    expect($codes)->toContain('computing', 'car_accessories', 'fitness_gym');
});

it('deduce interese din text liber in rusa', function () {
    $result = ($this->match)('Ему нравится рыбалка, футбол и хорошее вино');
    $codes = $result->pluck('interest.code');

    expect($codes)->toContain('fishing', 'team_sports', 'wine');
});

it('potriveste formele flexionate', function () {
    // "masinile" -> "masina", "citeste" -> "citit"
    expect(($this->match)('îi plac mașinile')->pluck('interest.code'))->toContain('car_accessories')
        ->and(($this->match)('citește foarte mult')->pluck('interest.code'))->toContain('books_fiction');
});

it('nu confunda prenumele si cuvintele scurte cu interese', function () {
    // Fiecare a fost un fals-pozitiv real inainte de a ridica pragul radacinii:
    //   "Alex" -> "alexa" (casa inteligenta)
    //   "special" -> "spectacol" (concerte)
    //   "футбол" -> "футболка" (imbracaminte)
    expect(($this->match)('Alex')->pluck('interest.code'))->not->toContain('smart_home')
        ->and(($this->match)('nimic special')->pluck('interest.code'))->not->toContain('concerts')
        ->and(($this->match)('футбол')->pluck('interest.code'))->not->toContain('apparel');
});

it('nu inventeaza interese', function () {
    expect(($this->match)(''))->toBeEmpty()
        ->and(($this->match)('qwerty zxcvb'))->toBeEmpty();
});

it('nu atribuie niciodata incredere maxima unui interes dedus', function () {
    // Deducerea din text sta sub datele confirmate — docs/04 § 3.
    $result = ($this->match)('îi place fotbalul, fitness-ul, alergarea și ciclismul');

    expect($result)->not->toBeEmpty()
        ->and($result->max('confidence'))->toBeLessThan(1.0);
});

it('marcheaza experientele separat de produse', function () {
    // Experientele se cauta in Experience Engine, nu in catalogul de produse.
    $karting = Interest::where('code', 'karting_exp')->first();
    $audio = Interest::where('code', 'audio')->first();

    expect($karting->is_experience)->toBeTrue()
        ->and($audio->is_experience)->toBeFalse();
});
