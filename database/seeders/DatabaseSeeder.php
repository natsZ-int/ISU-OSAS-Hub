<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesSeeder::class,
            AdminUserSeeder::class,
            ScholarshipSeeder::class,
            ScholarshipRequirementSeeder::class,
            ServiceTypeSeeder::class,
            GuidanceSeeder::class,
            CounselorAvailabilitySeeder::class,
            MatchingWeightSeeder::class,
        ]);
    }
}
