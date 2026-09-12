<?php

namespace App\Domain\People\Policies;

use App\Domain\People\Models\Person;
use App\Models\User;

/**
 * O persoană aparține unui singur utilizator și nu e vizibilă nimănui altcuiva.
 * Regula 3 din CLAUDE.md. Fiecare endpoint are un test care o verifică.
 */
class PersonPolicy
{
    public function view(User $user, Person $person): bool
    {
        return $person->user_id === $user->id;
    }

    public function update(User $user, Person $person): bool
    {
        return $person->user_id === $user->id;
    }

    public function delete(User $user, Person $person): bool
    {
        return $person->user_id === $user->id;
    }
}
