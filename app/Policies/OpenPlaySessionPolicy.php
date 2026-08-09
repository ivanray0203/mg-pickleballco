<?php

namespace App\Policies;

use App\Models\OpenPlaySession;
use App\Models\User;

class OpenPlaySessionPolicy
{
    /**
     * Open-play sessions are managed exclusively by administrators.
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

    public function update(User $user, OpenPlaySession $session): bool
    {
        return false;
    }
}
