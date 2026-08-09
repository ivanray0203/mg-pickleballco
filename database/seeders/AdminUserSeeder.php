<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed a default administrator account.
     *
     * Idempotent: running it repeatedly keeps a single admin and refreshes
     * the role, so an existing account with this email is promoted rather
     * than duplicated.
     */
    public function run(): void
    {
        $admin = User::firstOrNew(['email' => 'admin@example.com']);

        $admin->fill([
            'name' => 'Admin User',
            'password' => Hash::make('password'),
        ]);

        // `role` is intentionally guarded (not mass-assignable), so set it here.
        $admin->role = UserRole::Admin;
        $admin->forceFill(['email_verified_at' => now()]);
        $admin->save();
    }
}
