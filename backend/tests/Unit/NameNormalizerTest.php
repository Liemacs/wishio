<?php

use App\Support\Names\NameNormalizer;

beforeEach(function () {
    $this->n = new NameNormalizer;
});

it('elimina diacriticele romanesti, ambele variante de codare', function () {
    // In agendele reale apar si virgula (corect) si sedila (mostenire Windows-1250).
    expect($this->n->normalize('Ștefan'))->toBe('stefan')   // ș U+0219
        ->and($this->n->normalize('Ştefan'))->toBe('stefan') // ş U+015F
        ->and($this->n->normalize('ȘTEFAN'))->toBe('stefan')
        ->and($this->n->normalize('Gheorghiță'))->toBe('gheorghita')
        ->and($this->n->normalize('Ghiorghiţă'))->toBe('ghiorghita')
        ->and($this->n->normalize('Dănuț'))->toBe('danut')
        ->and($this->n->normalize('Mărioara'))->toBe('marioara');
});

it('trateaza ё ca е, la fel ca utf8mb4_unicode_ci', function () {
    // Daca PHP si baza de date nu produc aceleasi clase de echivalenta,
    // cautarile difera de constrangerile de unicitate.
    expect($this->n->normalize('Фёдор'))->toBe($this->n->normalize('Федор'));
});

it('pastreaza literele distincte pe care nici colatia nu le uneste', function () {
    expect($this->n->normalize('Андрей'))->not->toBe($this->n->normalize('Андреи'))
        ->and($this->n->normalize('Ольга'))->not->toBe($this->n->normalize('Олга'));
});

it('curata emoji, punctuatie si spatii multiple', function () {
    expect($this->n->normalize('  Ana ❤️  '))->toBe('ana')
        ->and($this->n->normalize('Ion (service)'))->toBe('ion service')
        ->and($this->n->normalize('Maria   Elena'))->toBe('maria elena');
});

it('extrage prenumele candidate cu ponderea increderii', function () {
    // Primul token e aproape intotdeauna prenumele; tokenii urmatori conteaza mai putin.
    expect($this->n->candidateWeights('Ion Popescu'))
        ->toBe(['ion popescu' => 1.0, 'ion' => 1.0, 'popescu' => 0.8]);

    expect($this->n->candidateWeights('Popescu Ion'))
        ->toBe(['popescu ion' => 1.0, 'popescu' => 1.0, 'ion' => 0.8]);

    expect($this->n->candidates('Ana ❤️'))->toBe(['ana'])
        ->and($this->n->candidates('Maria-Elena Rusu'))->toContain('maria');
});

it('respinge intrarile care nu sunt prenume', function () {
    // Agendele reale sunt pline de asa ceva.
    expect($this->n->candidates('Mama'))->toBe([])
        ->and($this->n->candidates('Taxi'))->toBe([])
        ->and($this->n->candidates('мама'))->toBe([])
        ->and($this->n->looksLikeName('Sefu'))->toBeFalse()
        ->and($this->n->looksLikeName('Ion'))->toBeTrue();
});
