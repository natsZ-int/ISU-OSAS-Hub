<?php

namespace Database\Seeders;

use App\Models\CounselorAvailability;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CounselorAvailabilitySeeder extends Seeder
{
    public function run(): void
    {
        // Find or create a development counselor user
        $counselorRole = Role::where('name', Role::COUNSELOR)->first();

        if (! $counselorRole) {
            $this->command->warn('Counselor role not found. Skipping availability seed.');
            return;
        }

        $counselor = User::where('role_id', $counselorRole->id)->first();

        if (! $counselor) {
            $counselor = User::create([
                'name'     => env('COUNSELOR_NAME', 'Dev Counselor'),
                'email'    => env('COUNSELOR_EMAIL', 'counselor@isu.local'),
                'password' => Hash::make(env('COUNSELOR_PASSWORD', 'ChangeMe!Counselor2025')),
                'role_id'  => $counselorRole->id,
            ]);
            $this->command->info("Created development counselor: {$counselor->email}");
        }

        // Seed Monday–Friday, 8:00–12:00 and 13:00–17:00
        $slots = [];
        for ($day = 1; $day <= 5; $day++) {
            $slots[] = ['counselor_id' => $counselor->id, 'day_of_week' => $day, 'start_time' => '08:00', 'end_time' => '12:00', 'is_available' => true];
            $slots[] = ['counselor_id' => $counselor->id, 'day_of_week' => $day, 'start_time' => '13:00', 'end_time' => '17:00', 'is_available' => true];
        }

        foreach ($slots as $slot) {
            CounselorAvailability::updateOrCreate(
                [
                    'counselor_id' => $slot['counselor_id'],
                    'day_of_week'  => $slot['day_of_week'],
                    'start_time'   => $slot['start_time'],
                ],
                $slot
            );
        }

        $this->command->info("Seeded 10 availability slots for {$counselor->email}.");
    }
}