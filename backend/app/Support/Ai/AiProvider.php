<?php

namespace App\Support\Ai;

use App\Domain\Recommendations\DTOs\GiftCriteria;
use App\Domain\Recommendations\DTOs\PersonContext;

interface AiProvider
{
    public function name(): string;

    /** Din profilul persoanei, criteriile de căutare în catalog. */
    public function generateGiftCriteria(PersonContext $context): GiftCriteria;

    /**
     * O explicație scurtă pentru fiecare produs ales.
     *
     * @param  list<string>  $titles  titlurile DEJA selectate din catalog
     * @return array<int, string> indexate la fel ca $titles
     */
    public function explain(array $titles, PersonContext $context, string $locale): array;
}
