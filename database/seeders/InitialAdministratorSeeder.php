<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use InvalidArgumentException;

class InitialAdministratorSeeder extends Seeder
{
    public function run(): void
    {
        if (User::query()->exists()) {
            $this->command?->info('Initial administrator was not created because user accounts already exist.');

            return;
        }

        $name = env('INITIAL_ADMIN_NAME', 'WIMS Administrator');
        $email = env('INITIAL_ADMIN_EMAIL');
        $password = env('INITIAL_ADMIN_PASSWORD');

        if (! is_string($email) || ! is_string($password)) {
            $this->command?->warn('Initial administrator was not created. Set INITIAL_ADMIN_EMAIL and INITIAL_ADMIN_PASSWORD before seeding.');

            return;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12) {
            throw new InvalidArgumentException('Set a valid INITIAL_ADMIN_EMAIL and an INITIAL_ADMIN_PASSWORD of at least 12 characters.');
        }

        User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => User::ROLE_ADMINISTRATOR,
            'is_active' => true,
        ]);

        $this->command?->info("Initial administrator created: {$email}");
    }
}
