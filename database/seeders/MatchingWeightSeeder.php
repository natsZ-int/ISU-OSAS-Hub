<?php

namespace Database\Seeders;

use App\Models\ScholarshipMatchingWeight;
use Illuminate\Database\Seeder;

class MatchingWeightSeeder extends Seeder
{
    public function run(): void
    {
        ScholarshipMatchingWeight::firstOrCreate(
            ['is_active' => true],
            [
                'academic_weight' => 30.00,
                'financial_weight' => 25.00,
                'program_weight' => 20.00,
                'year_level_weight' => 15.00,
                'document_readiness_weight' => 10.00,
            ]
        );
    }
}
