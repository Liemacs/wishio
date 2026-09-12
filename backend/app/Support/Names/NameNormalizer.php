<?php

namespace App\Support\Names;

/**
 * Normalizeaza prenume pentru potrivirea la onomastici.
 *
 * Doua probleme distincte:
 *  1. Diacriticele romanesti — "Ștefan", "Stefan", "ŞTEFAN" trebuie sa fie acelasi lucru.
 *     Atentie: in romana se folosesc DOUA seturi de caractere pentru s si t —
 *     cu virgula (ș U+0219, ț U+021B, corect) si cu sedila (ş U+015F, ţ U+0163,
 *     mostenire din Windows-1250). Agendele reale contin ambele.
 *  2. Chirilicul — nu transliteram generic, pentru ca "Георгий" -> "georgii" nu
 *     ajuta la nimic. Formele chirilice se stocheaza ca aliasuri proprii.
 *     Transliterarea ramane doar ca ultima incercare.
 *
 * Vezi docs/04-model-domeniu.md § 4.
 */
class NameNormalizer
{
    /** Diacritice romanesti, ambele variante, plus cele uzuale din regiune. */
    private const DIACRITICS = [
        'ă' => 'a', 'â' => 'a', 'à' => 'a', 'á' => 'a', 'ä' => 'a',
        'î' => 'i', 'í' => 'i', 'ï' => 'i',
        'ș' => 's', 'ş' => 's', 'š' => 's',   // virgula si sedila
        'ț' => 't', 'ţ' => 't',               // virgula si sedila
        'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
        'ó' => 'o', 'ö' => 'o', 'ô' => 'o',
        'ú' => 'u', 'ü' => 'u', 'ù' => 'u',
        'ç' => 'c', 'ñ' => 'n', 'ý' => 'y',

        // Chirilic: 'ё' este 'е' cu diacritic, iar colatia utf8mb4_unicode_ci
        // le considera egale. Normalizatorul PHP TREBUIE sa produca aceleasi
        // clase de echivalenta ca baza de date, altfel cautarile difera de
        // constrangerile de unicitate. Verificat pe MariaDB 10.4:
        //   'ё'='е' -> 1 · 'й'='и' -> 0 · 'щ'='ш' -> 0 · 'ь' NU e ignorat
        'ё' => 'е',
    ];

    /** Folosita doar ca ultima incercare, cand nu exista alias chirilic. */
    private const CYRILLIC = [
        'а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'e','ж'=>'j',
        'з'=>'z','и'=>'i','й'=>'i','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o',
        'п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'h','ц'=>'t',
        'ч'=>'c','ш'=>'s','щ'=>'s','ъ'=>'','ы'=>'i','ь'=>'','э'=>'e','ю'=>'iu',
        'я'=>'ia',
    ];

    /** Cuvinte care nu sunt prenume — apar des in agende. */
    private const NOT_A_NAME = [
        'mama','tata','mami','tati','bunica','bunicu','bunicul','sora','frate',
        'sef','sefu','sefa','doctor','dr','taxi','service','urgenta','politie',
        'acasa','birou','munca','work','home','office','мама','папа','бабушка',
        'дедушка','сестра','брат','врач','такси','работа','дом',
    ];

    public function normalize(string $name): string
    {
        $name = mb_strtolower(trim($name));
        $name = strtr($name, self::DIACRITICS);

        // pastreaza doar litere (latine si chirilice) si spatiu
        $name = preg_replace('/[^\p{L}\s]/u', '', $name) ?? '';

        return trim(preg_replace('/\s+/', ' ', $name) ?? '');
    }

    public function transliterate(string $name): string
    {
        return strtr($this->normalize($name), self::CYRILLIC);
    }

    public function isCyrillic(string $name): bool
    {
        return (bool) preg_match('/\p{Cyrillic}/u', $name);
    }

    /**
     * Prenumele candidate dintr-un nume de contact, cu ponderea increderii.
     *
     * "Ion Popescu"      -> ["ion popescu" => 1.0, "ion" => 1.0, "popescu" => 0.8]
     * "Popescu Ion"      -> ["popescu ion" => 1.0, "popescu" => 1.0, "ion" => 0.8]
     * "Maria-Elena Rusu" -> ["maria" => 1.0, "elena" => 0.8, "rusu" => 0.8]
     * "Mama"             -> []
     *
     * Primul token este aproape intotdeauna prenumele, in ambele culturi.
     * O potrivire pe un token ulterior e reala, dar mai putin sigura — de aceea
     * ponderea, nu pozitia in lista: numele compus ocupa altfel prima pozitie
     * si ar penaliza gresit prenumele propriu-zis.
     *
     * @return array<string, float>
     */
    public function candidateWeights(string $contactName): array
    {
        $normalized = $this->normalize(str_replace('-', ' ', $contactName));

        if ($normalized === '') {
            return [];
        }

        $tokens = array_values(array_filter(
            explode(' ', $normalized),
            fn (string $t) => mb_strlen($t) >= 2 && ! in_array($t, self::NOT_A_NAME, true)
        ));

        if ($tokens === []) {
            return [];
        }

        $weights = [];

        // Numele compus intreg, daca are exact doua parti ("maria elena").
        if (count($tokens) === 2) {
            $weights[implode(' ', $tokens)] = 1.0;
        }

        foreach ($tokens as $i => $token) {
            $weight = $i === 0 ? 1.0 : 0.8;
            $weights[$token] = max($weights[$token] ?? 0.0, $weight);
        }

        return $weights;
    }

    /** @return list<string> */
    public function candidates(string $contactName): array
    {
        return array_keys($this->candidateWeights($contactName));
    }

    public function looksLikeName(string $contactName): bool
    {
        return $this->candidates($contactName) !== [];
    }
}
