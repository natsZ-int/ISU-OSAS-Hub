<?php

namespace Tests\Feature\Scholarship;

use App\Models\Role;
use App\Models\Scholarship;
use App\Models\ScholarshipApplication;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $inactiveAdmin;

    protected ScholarshipApplication $approvedApp;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::create(['name' => 'admin',   'label' => 'Administrator']);
        $studentRole = Role::create(['name' => 'student', 'label' => 'Student']);

        $this->admin = User::factory()->create(['role_id' => $adminRole->id, 'is_active' => true]);
        $this->inactiveAdmin = User::factory()->create(['role_id' => $adminRole->id, 'is_active' => false]);

        $studentUser = User::factory()->create(['role_id' => $studentRole->id, 'is_active' => true]);
        $profile = StudentProfile::create([
            'user_id' => $studentUser->id,
            'student_number' => '2026-WF-01',
            'first_name' => 'WF',
            'last_name' => 'Test',
            'program' => 'BSCS',
            'year_level' => 3,
            'gwa' => 1.50,
            'enrollment_status' => 'enrolled',
        ]);

        $scholarship = Scholarship::create([
            'title' => 'WF Grant',
            'description' => 'desc',
            'provider' => 'CHED',
            'scholarship_type' => 'academic',
            'status' => 'open',
            'application_start_date' => now()->subDays(5),
            'application_deadline' => now()->addDays(30),
            'created_by' => $this->admin->id,
        ]);

        $this->approvedApp = ScholarshipApplication::create([
            'application_number' => 'APP-WF-01',
            'student_profile_id' => $profile->id,
            'scholarship_id' => $scholarship->id,
            'status' => 'approved',
            'submitted_at' => now()->subDays(2),
        ]);
    }

    public function test_terminal_approved_state_cannot_transition_further(): void
    {
        $this->actingAs($this->admin)
            ->patch(route('admin.scholarship-applications.transition', $this->approvedApp), [
                'status' => 'under_review',
                'remarks' => 'Trying to go back',
            ])
            ->assertRedirect();

        $this->approvedApp->refresh();
        $this->assertEquals('approved', $this->approvedApp->status instanceof \BackedEnum
            ? $this->approvedApp->status->value
            : $this->approvedApp->status);
    }

    public function test_inactive_admin_cannot_transition_application(): void
    {
        $this->actingAs($this->inactiveAdmin)
            ->patch(route('admin.scholarship-applications.transition', $this->approvedApp), [
                'status' => 'rejected',
                'remarks' => 'test',
            ])
            ->assertRedirect(route('login'));
    }
}
