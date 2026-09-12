<?php

namespace App\Domain\People\Actions;

use App\Domain\People\Enums\FieldSource;
use App\Domain\People\Models\Person;
use App\Domain\People\Models\PersonFieldSource;

/**
 * Scrie un câmp pe o persoană, respectând ierarhia de încredere.
 *
 * Singura cale prin care câmpurile cu proveniență se modifică. Vezi
 * docs/04-model-domeniu.md § 3 și regula 7 din CLAUDE.md.
 *
 * Regulile, în ordine:
 *  1. Un câmp modificat manual de proprietar nu mai e atins NICIODATĂ de o
 *     sursă automată — nici de sincronizarea contactelor, nici de claim.
 *     Dacă ai redenumit-o „Daniela ❤️”, aplicația nu are voie să revină.
 *  2. În rest, câștigă sursa cu încredere mai mare sau egală.
 *  3. O sursă inferioară e respinsă în tăcere: nu e o eroare, e o sincronizare
 *     care n-avea ce adăuga.
 */
class WritePersonField
{
    /** Câmpurile care au proveniență urmărită. */
    public const TRACKED = [
        'display_name', 'birth_date', 'birth_year_known',
        'gender', 'relationship', 'avatar_path',
    ];

    public function __invoke(
        Person $person,
        string $field,
        mixed $value,
        FieldSource $source,
        ?float $confidence = null,
    ): bool {
        if (! in_array($field, self::TRACKED, true)) {
            throw new \InvalidArgumentException("Câmpul [$field] nu are proveniență urmărită.");
        }

        $existing = $person->sourceFor($field);

        if (! $this->mayWrite($existing, $source)) {
            return false;
        }

        $person->forceFill([$field => $value])->save();

        PersonFieldSource::updateOrCreate(
            ['person_id' => $person->id, 'field' => $field],
            [
                'source'     => $source,
                'confidence' => $confidence ?? $source->defaultConfidence(),
                // Marcajul se pune o singură dată și nu se mai ridică:
                // o modificare manuală protejează câmpul definitiv.
                'overridden_at' => $source === FieldSource::OwnerManual
                    ? ($existing?->overridden_at ?? now())
                    : $existing?->overridden_at,
            ]
        );

        $person->unsetRelation('fieldSources');

        return true;
    }

    private function mayWrite(?PersonFieldSource $existing, FieldSource $incoming): bool
    {
        if ($existing === null) {
            return true;
        }

        /*
         * Proprietarul poate întotdeauna să-și editeze propriul contact.
         *
         * Ierarhia guvernează scrierile AUTOMATE, nu acțiunile omului. Dacă
         * Ana își completează numele ca „Ana Casianov” prin linkul public,
         * Maxim trebuie să poată totuși s-o salveze ca „Ana ❤️” la el în
         * agendă — e vederea LUI asupra contactului LUI.
         *
         * Fără excepția asta, aplicația i-ar ignora editarea în tăcere.
         */
        if ($incoming === FieldSource::OwnerManual) {
            return true;
        }

        // Modificarea manuală e definitivă față de orice sursă ulterioară.
        if ($existing->overridden_at !== null) {
            return false;
        }

        // În rest: egal sau mai de încredere.
        return $incoming->outranksOrEquals($existing->source);
    }
}
