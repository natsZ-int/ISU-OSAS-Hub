<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => Role::ADMIN,     'label' => 'Administrator'],
            ['name' => Role::COUNSELOR, 'label' => 'Counselor'],
            ['name' => Role::STUDENT,   'label' => 'Student'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['name' => $role['name']], $role);
        }
    }
}