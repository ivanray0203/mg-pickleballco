<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * A booking may be viewed by an admin or by the customer who owns it.
     */
    public function view(User $user, Booking $booking): bool
    {
        return $user->isAdmin() || $booking->user_id === $user->id;
    }

    /**
     * Only admins may create bookings on behalf of a customer (walk-ins).
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Only admins may cancel, and only bookings that are still eligible.
     */
    public function cancel(User $user, Booking $booking): bool
    {
        return $user->isAdmin() && $booking->isCancellable();
    }
}
