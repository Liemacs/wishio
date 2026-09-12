<?php

namespace App\Domain\Catalog\Actions;

/**
 * Cheia după care unim aceleași căști vândute la trei magazine.
 *
 * Titlurile diferă mult între magazine:
 *   „Sony WH-1000XM5 Black”
 *   „Căști wireless Sony WH-1000XM5, negru”
 * Codul de model e semnalul cel mai stabil.
 *
 * ⚠️ NU folosește NameNormalizer: acela elimină cifrele, ceea ce e corect
 * pentru prenume și fatal pentru coduri de produs. „WH-1000XM5” ar deveni
 * „whxm”, iar produsele nu s-ar mai uni niciodată.
 *
 * Regula de prudență din docs/05 § 4: mai bine două produse duplicate decât
 * două produse diferite îmbinate greșit.
 */
class BuildCanonicalKey
{
    /** Sufixe de unitate. „100ml” și „100 ml” trebuie să însemne același lucru. */
    private const UNITS = [
        'mm', 'cm', 'm', 'ml', 'l', 'g', 'kg', 'gb', 'tb', 'mb',
        'w', 'v', 'ah', 'mah', 'inch', 'oz', 'hz', 'buc', 'ks', 'st',
    ];

    /** Cuvinte care apar în titluri și nu spun nimic despre produs. */
    private const NOISE = [
        'casti', 'set', 'nou', 'noua', 'original', 'originala', 'garantie',
        'negru', 'alb', 'rosu', 'albastru', 'gri', 'verde', 'roz', 'auriu',
        'wireless', 'bluetooth', 'portabil', 'portabila',
        'black', 'white', 'red', 'blue', 'grey', 'gray', 'green', 'gold',
        'chernyy', 'belyy', 'krasnyy', 'siniy', 'seryy',
        ...self::UNITS,
    ];

    private const DIACRITICS = [
        'ă' => 'a', 'â' => 'a', 'î' => 'i', 'ș' => 's', 'ş' => 's', 'ț' => 't', 'ţ' => 't',
        'é' => 'e', 'è' => 'e', 'ö' => 'o', 'ü' => 'u', 'ä' => 'a', 'ñ' => 'n', 'ç' => 'c',
    ];

    public function __invoke(string $title, ?string $brand = null): string
    {
        $brandKey = $brand ? implode('', $this->tokenize($brand)) : '';
        $tokens   = $this->tokenize($title);

        /*
         * Un cod de model are ȘI litere, ȘI cifre: „1000xm5”, „3s”, „a54”.
         * Tokenii pur numerici („100”, „44”) sunt periculoși ca cheie — ar uni
         * „Dior Sauvage 100ml” cu „Dior J'adore 100ml”, două parfumuri diferite.
         */
        $models = array_values(array_unique(array_filter(
            $tokens,
            fn (string $token) => mb_strlen($token) >= 2
                && preg_match('/\d/', $token)
                && preg_match('/\p{L}/u', $token)
                && ! in_array($token, self::NOISE, true)
        )));

        if ($models !== []) {
            sort($models);

            return trim($brandKey.'|'.implode('-', $models), '|');
        }

        // Fără cod de model, nu ghicim: cheia e titlul curățat.
        $words = array_values(array_unique(array_filter(
            $tokens,
            fn (string $token) => mb_strlen($token) >= 2
                && ! in_array($token, self::NOISE, true)
                && ! preg_match('/^\d+$/', $token)
        )));

        sort($words);

        return trim($brandKey.'|'.implode('-', $words), '|');
    }

    /** @return list<string> */
    private function tokenize(string $value): array
    {
        $value = strtr(mb_strtolower(trim($value)), self::DIACRITICS);

        // Cifrele rămân. Separatorii devin spații: „WH-1000XM5” → „wh 1000xm5”.
        $value = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value) ?? '';

        $tokens = [];

        foreach (array_filter(explode(' ', trim($value))) as $token) {
            // „100ml” → „100” + „ml”; unitatea cade apoi ca zgomot.
            if (preg_match('/^(\d+)('.implode('|', self::UNITS).')$/u', $token, $matches)) {
                $tokens[] = $matches[1];
                $tokens[] = $matches[2];

                continue;
            }

            $tokens[] = $token;
        }

        return $tokens;
    }
}
