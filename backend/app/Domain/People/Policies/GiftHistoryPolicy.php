<?php

namespace App\Domain\People\Policies;

use App\Domain\People\Models\GiftHistory;
use App\Models\User;

class GiftHistoryPolicy
{
    public function delete(User $user, GiftHistory $entry): bool
    {
        return $entry->user_id === $user->id;
    }
}
