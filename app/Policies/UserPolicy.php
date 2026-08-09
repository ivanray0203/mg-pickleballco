<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Only admins may browse the customer directory.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Admins may view a customer's record. Admin accounts are not customers
     * and are never surfaced through customer management.
     */
    public function view(User $user, User $model): bool
    {
        return $user->isAdmin() && $model->isCustomer();
    }
}
