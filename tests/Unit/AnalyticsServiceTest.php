<?php

namespace Tests\Unit;

use App\Enums\ScholarshipApplicationStatus;
use App\Models\Role;
use App\Models\Scholarship;
use App\Models\ScholarshipApplication;
use App\Models\ServiceType;
use App\Models\StudentProfile;
use App\Models\StudentServiceRequest;
use App\Models\User;
use App\Services\AnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AnalyticsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AnalyticsService;
    }

    public function test_filter_validation_rejects_inverted_dates_and_invalid_ids(): void
    {
        $raw = [
            'date_from' => '2026-05-01',
            'date_to' => '2026-01-01', // Earlier than date_from
            'scholarship_id' => 99999,        // Non-existent ID
            'appointment_type_id' => 'malicious_input',
        ];

        $validated = $this->service->validateFilters($raw);

        $this->assertArrayNotHasKey('date_from', $validated);
        $this->assertArrayNotHasKey('date_to', $validated);
        $this->assertArrayNotHasKey('scholarship_id', $validated);
        $this->assertArrayNotHasKey('appointment_type_id', $validated);
    }

    public function test_approval_and_rejection_rates_exclude_pending_applications(): void
    {
        $adminRole = Role::create(['name' => 'admin', 'label' => 'Administrator']);
        $studentRole = Role::create(['name' => 'student', 'label' => 'Student']);

        $admin = User::factory()->create(['role_id' => $adminRole->id]);

        $scholarship = Scholarship::create([
            'title' => 'Academic Excellence',
            'description' => 'Test scholarship description',
            'provider' => 'CHED',
            'scholarship_type' => 'academic',
            'status' => 'open',
            'application_start_date' => now()->subDays(10),
            'application_deadline' => now()->addDays(20),
            'created_by' => $admin->id,
        ]);

        $createStudentProfile = function (int $id) use ($studentRole) {
            $user = User::factory()->create(['role_id' => $studentRole->id]);

            return StudentProfile::create([
                'user_id' => $user->id,
                'student_number' => sprintf('2026-%04d', $id),
                'first_name' => 'Student',
                'last_name' => "Number{$id}",
                'program' => 'BSCS',
                'year_level' => 3,
                'gwa' => 1.50,
                'enrollment_status' => 'enrolled',
            ]);
        };

        $profileCounter = 1;

        // 4 Approved
        for ($i = 0; $i < 4; $i++) {
            $p = $createStudentProfile($profileCounter++);
            ScholarshipApplication::create([
                'application_number' => "APP-A-{$i}",
                'student_profile_id' => $p->id,
                'scholarship_id' => $scholarship->id,
                'status' => ScholarshipApplicationStatus::APPROVED->value,
                'submitted_at' => now()->subDays(2),
            ]);
        }

        // 1 Rejected
        $pR = $createStudentProfile($profileCounter++);
        ScholarshipApplication::create([
            'application_number' => 'APP-R-1',
            'student_profile_id' => $pR->id,
            'scholarship_id' => $scholarship->id,
            'status' => ScholarshipApplicationStatus::REJECTED->value,
            'submitted_at' => now()->subDays(2),
        ]);

        // 5 Pending (submitted/under_review/document_verification)
        $pendingStatuses = [
            ScholarshipApplicationStatus::SUBMITTED->value,
            ScholarshipApplicationStatus::UNDER_REVIEW->value,
            ScholarshipApplicationStatus::DOCUMENT_VERIFICATION->value,
            ScholarshipApplicationStatus::SUBMITTED->value,
            ScholarshipApplicationStatus::UNDER_REVIEW->value,
        ];

        foreach ($pendingStatuses as $idx => $status) {
            $pP = $createStudentProfile($profileCounter++);
            ScholarshipApplication::create([
                'application_number' => "APP-P-{$idx}",
                'student_profile_id' => $pP->id,
                'scholarship_id' => $scholarship->id,
                'status' => $status,
                'submitted_at' => now()->subDays(1),
            ]);
        }

        $analytics = $this->service->getScholarshipAnalytics([]);

        // Total decided = 4 approved + 1 rejected = 5
        // Approval rate = 4 / 5 * 100 = 80.0%
        // Rejection rate = 1 / 5 * 100 = 20.0%
        $this->assertEquals(4, $analytics['approved']);
        $this->assertEquals(1, $analytics['rejected']);
        $this->assertEquals(5, $analytics['pending']);
        $this->assertEquals(5, $analytics['decided']);
        $this->assertEquals(80.0, $analytics['approval_rate']);
        $this->assertEquals(20.0, $analytics['rejection_rate']);
    }

    public function test_rates_return_null_when_there_are_zero_decided_applications(): void
    {
        $analytics = $this->service->getScholarshipAnalytics([]);

        $this->assertNull($analytics['approval_rate']);
        $this->assertNull($analytics['rejection_rate']);
        $this->assertEquals(0, $analytics['decided']);
    }

    public function test_service_request_processing_time_calculates_difference_and_excludes_incomplete(): void
    {
        $studentRole = Role::create(['name' => 'student', 'label' => 'Student']);
        $studentUser = User::factory()->create(['role_id' => $studentRole->id]);
        $profile = StudentProfile::create([
            'user_id' => $studentUser->id,
            'student_number' => '2026-0002',
            'first_name' => 'John',
            'last_name' => 'Smith',
            'program' => 'BSIT',
            'year_level' => 2,
            'gwa' => 1.75,
            'enrollment_status' => 'enrolled',
        ]);

        $serviceType = ServiceType::create([
            'name' => 'Good Moral Certificate',
            'description' => 'Certificate issuance',
            'is_active' => true,
        ]);

        // Request 1: 24 hours processing time
        StudentServiceRequest::create([
            'request_number' => 'REQ-001',
            'student_profile_id' => $profile->id,
            'service_type_id' => $serviceType->id,
            'description' => 'Request for scholarship application certificate',
            'status' => StudentServiceRequest::STATUS_COMPLETED,
            'submitted_at' => now()->subHours(48),
            'completed_at' => now()->subHours(24),
        ]);

        // Request 2: 48 hours processing time
        StudentServiceRequest::create([
            'request_number' => 'REQ-002',
            'student_profile_id' => $profile->id,
            'service_type_id' => $serviceType->id,
            'description' => 'Request for transfer certificate',
            'status' => StudentServiceRequest::STATUS_COMPLETED,
            'submitted_at' => now()->subHours(72),
            'completed_at' => now()->subHours(24),
        ]);

        // Request 3: Pending, no completed_at -> MUST NOT affect average
        StudentServiceRequest::create([
            'request_number' => 'REQ-003',
            'student_profile_id' => $profile->id,
            'service_type_id' => $serviceType->id,
            'description' => 'Pending document review request',
            'status' => StudentServiceRequest::STATUS_PROCESSING,
            'submitted_at' => now()->subHours(10),
            'completed_at' => null,
        ]);

        $analytics = $this->service->getServiceAnalytics([]);

        // Average = (24 + 48) / 2 = 36 hours (1.5 days)
        $this->assertEquals(36.0, $analytics['avg_processing_hours']);
        $this->assertEquals(1.5, $analytics['avg_processing_days']);
    }
}
