<?php

namespace App\Domain\People\Policies;

use App\Domain\People\Models\GiftIdea;
use App\Models\User;

class GiftIdeaPolicy
{
    public function update(User $user, GiftIdea $idea): bool
    {
        return $idea->user_id === $user->id;
    }

    public function delete(User $user, GiftIdea $idea): bool
    {
        return $idea->user_id === $user->id;
    }
}
