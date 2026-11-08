<?php

namespace Tests\Feature\Audit;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\Scholarship;
use App\Models\ScholarshipApplication;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_workflow_transition_does_not_create_success_audit(): void
    {
        $adminRole = Role::create(['name' => 'admin',   'label' => 'Administrator']);
        $studentRole = Role::create(['name' => 'student', 'label' => 'Student']);

        $admin = User::factory()->create(['role_id' => $adminRole->id, 'is_active' => true]);
        $studentUser = User::factory()->create(['role_id' => $studentRole->id, 'is_active' => true]);
        $profile = StudentProfile::create([
            'user_id' => $studentUser->id,
            'student_number' => '2026-AUD-01',
            'first_name' => 'Audit',
            'last_name' => 'Test',
            'program' => 'BSCS',
            'year_level' => 3,
            'gwa' => 1.50,
            'enrollment_status' => 'enrolled',
        ]);

        $scholarship = Scholarship::create([
            'title' => 'Audit Grant',
            'description' => 'desc',
            'provider' => 'CHED',
            'scholarship_type' => 'academic',
            'status' => 'open',
            'application_start_date' => now()->subDays(5),
            'application_deadline' => now()->addDays(30),
            'created_by' => $admin->id,
        ]);

        $app = ScholarshipApplication::create([
            'application_number' => 'APP-AUD-01',
            'student_profile_id' => $profile->id,
            'scholarship_id' => $scholarship->id,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $initialAuditCount = AuditLog::where('record_type', 'ScholarshipApplication')
            ->where('record_id', $app->id)
            ->count();

        // Attempt invalid transition: submitted -> approved (FSM must reject)
        $this->actingAs($admin)
            ->patch(route('admin.scholarship-applications.transition', $app), [
                'status' => 'approved',
                'remarks' => 'skip the FSM',
            ])
            ->assertRedirect();

        $app->refresh();
        $this->assertEquals('submitted', $app->status instanceof \BackedEnum
            ? $app->status->value
            : $app->status);

        // No "approved" audit should exist for this app
        $this->assertEquals(0, AuditLog::where('record_type', 'ScholarshipApplication')
            ->where('record_id', $app->id)
            ->where('action', 'like', '%Approved%')
            ->count());
    }
}
