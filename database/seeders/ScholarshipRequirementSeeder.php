<?php

namespace Database\Seeders;

use App\Models\Scholarship;
use App\Models\ScholarshipRequirement;
use Illuminate\Database\Seeder;

class ScholarshipRequirementSeeder extends Seeder
{
    public function run(): void
    {
        $requirements = [
            'ISU Academic Excellence Grant' => [
                ['requirement_name' => 'Certificate of Enrollment', 'is_required' => true, 'display_order' => 1],
                ['requirement_name' => 'Academic Transcript (TOR)', 'is_required' => true, 'display_order' => 2],
                ['requirement_name' => 'Good Moral Certificate', 'is_required' => true, 'display_order' => 3],
            ],
            'Community Support Scholarship' => [
                ['requirement_name' => 'Certificate of Enrollment', 'is_required' => true, 'display_order' => 1],
                ['requirement_name' => 'Academic Transcript (TOR)', 'is_required' => true, 'display_order' => 2],
                ['requirement_name' => 'Proof of Family Income (ITR or Certificate of Indigency)', 'is_required' => true, 'display_order' => 3],
                ['requirement_name' => 'Barangay Certificate of Residency', 'is_required' => true, 'display_order' => 4],
                ['requirement_name' => 'Recommendation Letter from Community Leader', 'is_required' => false, 'display_order' => 5],
            ],
            'STEM Development Scholarship' => [
                ['requirement_name' => 'Certificate of Enrollment', 'is_required' => true, 'display_order' => 1],
                ['requirement_name' => 'Academic Transcript (TOR)', 'is_required' => true, 'display_order' => 2],
                ['requirement_name' => 'Proof of Family Income (ITR)', 'is_required' => true, 'display_order' => 3],
                ['requirement_name' => 'Recommendation Letter from Department Chair', 'is_required' => true, 'display_order' => 4],
            ],
        ];

        foreach ($requirements as $scholarshipTitle => $reqs) {
            $scholarship = Scholarship::where('title', $scholarshipTitle)->first();
            if (! $scholarship) {
                continue;
            }

            foreach ($reqs as $req) {
                ScholarshipRequirement::updateOrCreate(
                    [
                        'scholarship_id'   => $scholarship->id,
                        'requirement_name' => $req['requirement_name'],
                    ],
                    array_merge($req, ['scholarship_id' => $scholarship->id])
                );
            }
        }
    }
}