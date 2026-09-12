<?php

namespace App\Support\Ai;

use App\Domain\Recommendations\DTOs\GiftCriteria;
use App\Domain\Recommendations\DTOs\PersonContext;

/**
 * Ruta fără AI. Implicită în dezvoltare și obligatorie în producție.
 *
 * Aplicația trebuie să funcționeze integral și pentru cine refuză
 * consimțământul de a trimite date către un AI terț (docs/06 § 2). Fără ea,
 * refuzul ar însemna un produs mort — ceea ce transformă „consimțământul”
 * într-o formalitate.
 *
 * Criteriile vin direct din ce știm despre persoană. Nu e mai puțin corect,
 * doar mai puțin creativ: nu propune categorii la care nu ne-am gândit.
 */
class RuleBasedAiProvider implements AiProvider
{
    public function name(): string
    {
        return 'rules';
    }

    public function generateGiftCriteria(PersonContext $context): GiftCriteria
    {
        return new GiftCriteria(
            interests: $context->interests,
            avoidInterests: $context->avoid,
            priceMin: $context->budgetMin,
            priceMax: $context->budgetMax,
            // Nu pretindem încredere mare: sunt exact interesele introduse,
            // fără nicio deducere peste ele.
            confidence: $context->interests === [] ? 0.2 : 0.7,
        );
    }

    public function explain(array $titles, PersonContext $context, string $locale): array
    {
        // Fără AI nu inventăm explicații. Interfața arată produsele fără
        // motivație, ceea ce e onest.
        return [];
    }
}
