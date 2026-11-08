<?php

namespace Database\Seeders;

use App\Models\ServiceType;
use Illuminate\Database\Seeder;

class ServiceTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'name' => 'Document Request & Certification',
                'description' => 'Official certifications, good moral character certificates, and student records issued by OSAS.',
                'instructions' => 'Please provide the purpose of your document request and upload a copy of your valid Student ID.',
                'is_active' => true,
                'display_order' => 1,
                'requirements' => [
                    [
                        'name' => 'Valid Student ID / Enrollment Form',
                        'description' => 'Clear photo or scanned PDF of your current semester ID or COR.',
                        'is_required' => true,
                        'display_order' => 1,
                    ],
                    [
                        'name' => 'Official Receipt / Request Slip (Optional)',
                        'description' => 'If applicable, upload your payment receipt or request endorsement slip.',
                        'is_required' => false,
                        'display_order' => 2,
                    ],
                ],
            ],
            [
                'name' => 'Student Clearance Sign-off',
                'description' => 'Official signing of clearance forms for graduation, transfer, or honorable dismissal.',
                'instructions' => 'Upload your filled clearance form. Make sure previous departmental signatories have signed before submitting.',
                'is_active' => true,
                'display_order' => 2,
                'requirements' => [
                    [
                        'name' => 'Clearance Form (Scanned PDF)',
                        'description' => 'Completed clearance form with all prior college signatures.',
                        'is_required' => true,
                        'display_order' => 1,
                    ],
                ],
            ],
            [
                'name' => 'Student Financial/Assistance Inquiry',
                'description' => 'Inquiries regarding student financial aid, emergency subsidies, or special grants.',
                'instructions' => 'Explain your inquiry or situation clearly. Supporting documents are optional.',
                'is_active' => true,
                'display_order' => 3,
                'requirements' => [
                    [
                        'name' => 'Endorsement / Supporting Letter',
                        'description' => 'Optional recommendation or endorsement from your department head.',
                        'is_required' => false,
                        'display_order' => 1,
                    ],
                ],
            ],
            [
                'name' => 'General OSAS Service Inquiry',
                'description' => 'General student questions, assistance requests, and OSAS staff consultation.',
                'instructions' => 'Detail your questions or concerns in the explanation field.',
                'is_active' => true,
                'display_order' => 4,
                'requirements' => [],
            ],
        ];

        foreach ($types as $typeData) {
            $requirements = $typeData['requirements'];
            unset($typeData['requirements']);

            $serviceType = ServiceType::updateOrCreate(['name' => $typeData['name']], $typeData);

            foreach ($requirements as $req) {
                $serviceType->requirements()->updateOrCreate(
                    ['name' => $req['name']],
                    $req
                );
            }
        }
    }
}
