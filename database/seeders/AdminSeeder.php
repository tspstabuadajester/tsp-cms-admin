<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Seed the default admin user.
     */
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@content.com'],
            [
                'name' => 'Admin',
                'password' => 'admin1234',
                'email_verified_at' => now(),
                'status' => 'active',
            ],
        );

        $admin->syncRoles(['admin']);
    }
}
