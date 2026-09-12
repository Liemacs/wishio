<?php

namespace App\Domain\People\Enums;

/**
 * Sursa unei informații despre o persoană, cu nivelul ei de încredere.
 *
 * La conflict câștigă sursa superioară. Vezi docs/04-model-domeniu.md § 3
 * și regula 7 din CLAUDE.md.
 */
enum FieldSource: string
{
    /** Persoana însăși a confirmat, în aplicație. */
    case SubjectConfirmed = 'subject_confirmed';

    /** Persoana a completat linkul public (docs/09 § W2). */
    case SubjectProvided = 'subject_provided';

    /** Proprietarul contactului a scris manual. */
    case OwnerManual = 'owner_manual';

    /** Citit din agenda telefonului. */
    case DeviceContact = 'device_contact';

    /** Dedus determinist — de exemplu onomastica din prenume. */
    case Derived = 'derived';

    /** Dedus de AI. Cea mai mică încredere; se marchează vizual. */
    case AiInferred = 'ai_inferred';

    /** Mai mic = mai de încredere. */
    public function priority(): int
    {
        return match ($this) {
            self::SubjectConfirmed => 1,
            self::SubjectProvided  => 2,
            self::OwnerManual      => 3,
            self::DeviceContact    => 4,
            self::Derived          => 5,
            self::AiInferred       => 6,
        };
    }

    public function outranks(self $other): bool
    {
        return $this->priority() < $other->priority();
    }

    public function outranksOrEquals(self $other): bool
    {
        return $this->priority() <= $other->priority();
    }

    /** Sursele automate nu pot suprascrie niciodată o modificare manuală. */
    public function isAutomatic(): bool
    {
        return in_array($this, [self::DeviceContact, self::Derived, self::AiInferred], true);
    }

    /** Încrederea implicită, când apelantul nu o precizează. */
    public function defaultConfidence(): float
    {
        return match ($this) {
            self::SubjectConfirmed => 1.00,
            self::SubjectProvided  => 0.95,
            self::OwnerManual      => 0.90,
            self::DeviceContact    => 0.80,
            self::Derived          => 0.60,
            self::AiInferred       => 0.50,
        };
    }
}
