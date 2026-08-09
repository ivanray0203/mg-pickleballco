<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A privacy-conscious view of a customer for the admin panel. Only fields
 * required for booking operations are exposed — never password hashes,
 * two-factor secrets, tokens or other authentication metadata.
 *
 * Aggregates (bookings_count, upcoming_bookings_count, total_paid_amount) are
 * present only when the query loaded them via withCount()/withSum().
 *
 * @mixin User
 */
class CustomerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $totalPaid = (int) ($this->total_paid_amount ?? 0);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'joined_at' => $this->created_at?->toIso8601String(),
            'total_bookings' => (int) ($this->bookings_count ?? 0),
            'upcoming_bookings' => (int) ($this->upcoming_bookings_count ?? 0),
            'total_paid_amount' => round($totalPaid / 100, 2),
            'total_paid_formatted' => '₱'.number_format($totalPaid / 100, 2),
        ];
    }
}
