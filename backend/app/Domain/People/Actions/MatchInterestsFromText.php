<?php

namespace App\Domain\People\Actions;

use App\Domain\People\Models\Interest;
use App\Support\Names\NameNormalizer;
use Illuminate\Support\Collection;

/**
 * Deduce interese dintr-un text liber despre o persoana, pe cuvinte-cheie.
 *
 * Doua roluri:
 *  1. Ecranul P5 — "Spune-mi despre Alex" — cand AI-ul nu e disponibil sau
 *     utilizatorul a refuzat consimtamantul AI (docs/06 § 2).
 *  2. Validarea si completarea rezultatului AI: daca AI-ul propune un cod care
 *     nu exista in taxonomie, se arunca; daca rateaza un interes evident din
 *     text, se adauga de aici.
 *
 * Increderea nu atinge niciodata 1.0 — este dedusa, nu declarata.
 * Vezi ierarhia din docs/04-model-domeniu.md § 3.
 */
class MatchInterestsFromText
{
    /**
     * Potrivirea pe radacina se aplica DOAR cuvintelor de cel putin atatea litere.
     * Sub acest prag cerem potrivire exacta, altfel prenumele si cuvintele scurte
     * produc fals-pozitive: "Alex" ar prinde "alexa", "special" ar prinde "spectacol".
     */
    private const STEM_LENGTH = 5;

    public function __construct(private readonly NameNormalizer $normalizer) {}

    /**
     * @return Collection<int, array{interest: Interest, confidence: float, matched: list<string>}>
     */
    public function __invoke(string $text, int $limit = 8): Collection
    {
        $normalized = $this->normalizer->normalize($text);

        if ($normalized === '') {
            return collect();
        }

        $tokens = array_values(array_filter(
            explode(' ', $normalized),
            fn (string $t) => mb_strlen($t) >= 3
        ));

        return Interest::with('group')->get()
            ->map(function (Interest $interest) use ($normalized, $tokens): array {
                $matched = $this->matchedKeywords($interest, $normalized, $tokens);

                return [
                    'interest'   => $interest,
                    'confidence' => $this->confidence(count($matched)),
                    'matched'    => $matched,
                ];
            })
            ->filter(fn (array $row) => $row['matched'] !== [])
            ->sortByDesc('confidence')
            ->take($limit)
            ->values();
    }

    /** @return list<string> */
    private function matchedKeywords(Interest $interest, string $normalized, array $tokens): array
    {
        $matched = [];

        // Cautam in cuvintele-cheie din TOATE limbile: un vorbitor de rusa poate
        // scrie "ii place футбол", iar amestecul RO/RU e normal in Moldova.
        foreach ($interest->keywords as $keywords) {
            foreach ($keywords as $keyword) {
                $key = $this->normalizer->normalize($keyword);

                if ($key === '') {
                    continue;
                }

                $hit = str_contains($key, ' ')
                    ? str_contains($normalized, $key)          // expresie: potrivire directa
                    : $this->anyTokenMatches($tokens, $key);   // cuvant: potrivire pe radacina

                if ($hit) {
                    $matched[$key] = true;
                }
            }
        }

        return array_keys($matched);
    }

    /** @param list<string> $tokens */
    private function anyTokenMatches(array $tokens, string $keyword): bool
    {
        foreach ($tokens as $token) {
            if ($token === $keyword) {
                return true;
            }

            // Formele flexionate au radacina comuna: masina/masini, carte/carti.
            // Doar pentru cuvinte suficient de lungi — vezi STEM_LENGTH.
            if (mb_strlen($token) >= self::STEM_LENGTH
                && mb_strlen($keyword) >= self::STEM_LENGTH
                && mb_substr($token, 0, self::STEM_LENGTH) === mb_substr($keyword, 0, self::STEM_LENGTH)) {
                return true;
            }
        }

        return false;
    }

    private function confidence(int $hits): float
    {
        return match (true) {
            $hits >= 3  => 0.85,
            $hits === 2 => 0.75,
            default     => 0.60,
        };
    }
}
