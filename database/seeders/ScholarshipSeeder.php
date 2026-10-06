<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Scholarship;
use App\Models\User;
use Illuminate\Database\Seeder;

class ScholarshipSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::whereHas('role', fn ($q) => $q->where('name', Role::ADMIN))->first();

        $scholarships = [
            [
                'title'                  => 'ISU Academic Excellence Grant',
                'description'            => 'A merit-based grant for students with outstanding academic performance across all ISU campuses.',
                'provider'               => 'Isabela State University',
                'scholarship_type'       => 'Merit-Based',
                'minimum_gwa'            => 1.50,
                'maximum_family_income'  => null,
                'eligible_programs'      => null,
                'eligible_year_levels'   => [2, 3, 4],
                'application_start_date' => '2025-02-01',
                'application_deadline'   => '2025-03-31',
                'status'                 => 'open',
            ],
            [
                'title'                  => 'Community Support Scholarship',
                'description'            => 'Financial assistance for students from low-income families who demonstrate active community involvement.',
                'provider'               => 'ISU OSAS Office',
                'scholarship_type'       => 'Need-Based',
                'minimum_gwa'            => 2.50,
                'maximum_family_income'  => 150000.00,
                'eligible_programs'      => null,
                'eligible_year_levels'   => [1, 2, 3, 4],
                'application_start_date' => '2025-03-01',
                'application_deadline'   => '2025-04-30',
                'status'                 => 'open',
            ],
            [
                'title'                  => 'STEM Development Scholarship',
                'description'            => 'Scholarship for students enrolled in Science, Technology, Engineering, and Mathematics programs with strong academic records.',
                'provider'               => 'ISU College of Engineering',
                'scholarship_type'       => 'Program-Specific',
                'minimum_gwa'            => 2.00,
                'maximum_family_income'  => 300000.00,
                'eligible_programs'      => ['BSIT', 'BSCS', 'BSCE', 'BSEE', 'BSME'],
                'eligible_year_levels'   => [2, 3, 4],
                'application_start_date' => '2025-06-01',
                'application_deadline'   => '2025-07-31',
                'status'                 => 'draft',
            ],
        ];

        foreach ($scholarships as $data) {
            Scholarship::updateOrCreate(
                ['title' => $data['title']],
                array_merge($data, ['created_by' => $admin?->id])
            );
        }
    }
}