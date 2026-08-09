<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * A payment may be viewed by an admin or by the customer who owns its
     * booking.
     */
    public function view(User $user, Payment $payment): bool
    {
        return $user->isAdmin() || $payment->booking->user_id === $user->id;
    }
}
