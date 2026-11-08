<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('name', Role::ADMIN)->firstOrFail();

        $email = config('app.system_admin_email');
        $name = env('ADMIN_NAME', 'System Administrator');
        $password = env('ADMIN_PASSWORD');

        if (empty($password)) {
            $this->command->error('ADMIN_PASSWORD env variable is not set. Aborting admin seed.');

            return;
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'role_id' => $adminRole->id,
            ]
        );

        $this->command->info("Administrator ready: {$email}");
    }
}
