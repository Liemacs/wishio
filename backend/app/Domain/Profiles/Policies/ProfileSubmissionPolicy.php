<?php

namespace App\Domain\Profiles\Policies;

use App\Domain\Profiles\Models\ProfileSubmission;
use App\Models\User;

class ProfileSubmissionPolicy
{
    /** Doar proprietarul linkului alege cine este omul care l-a completat. */
    public function resolve(User $user, ProfileSubmission $submission): bool
    {
        return $submission->profile->user_id === $user->id;
    }
}
