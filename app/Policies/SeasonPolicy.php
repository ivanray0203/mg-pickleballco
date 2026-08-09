<?php

namespace App\Policies;

use App\Models\Season;
use App\Models\User;

class SeasonPolicy
{
    /**
     * Seasons are managed exclusively by administrators.
     */
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Season $season): bool
    {
        return false;
    }
}
