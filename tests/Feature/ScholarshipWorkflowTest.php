<?php

namespace Tests\Feature;

use App\Enums\ScholarshipApplicationStatus;
use App\Models\ApplicationDocument;
use App\Models\Role;
use App\Models\Scholarship;
use App\Models\ScholarshipApplication;
use App\Models\ScholarshipRequirement;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ScholarshipWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    private function admin(): User
    {
        $unique = uniqid();
        return User::create([
            'name'      => 'Admin',
            'email'     => "admin_{$unique}@test.local",
            'password'  => Hash::make('password'),
            'role_id'   => Role::where('name', 'admin')->first()->id,
            'is_active' => true,
        ]);
    }

    private function student(): User
    {
        $unique = uniqid();
        $user = User::create([
            'name'      => 'Student',
            'email'     => "student_{$unique}@test.local",
            'password'  => Hash::make('password'),
            'role_id'   => Role::where('name', 'student')->first()->id,
            'is_active' => true,
        ]);

        StudentProfile::create([
            'user_id'        => $user->id,
            'student_number' => "STU-{$unique}",
            'first_name'     => 'Test',
            'last_name'      => 'Student',
            'year_level'     => 2,
        ]);

        return $user;
    }

    private function counselor(): User
    {
        $unique = uniqid();
        return User::create([
            'name'      => 'Counselor',
            'email'     => "counselor_{$unique}@test.local",
            'password'  => Hash::make('password'),
            'role_id'   => Role::where('name', 'counselor')->first()->id,
            'is_active' => true,
        ]);
    }

    private function submittedApplication(?User $student = null): ScholarshipApplication
    {
        $student = $student ?? $this->student();
        $scholarship = Scholarship::create([
            'title'                  => 'Test Scholarship',
            'description'            => 'Test Description',
            'provider'               => 'ISU',
            'scholarship_type'       => 'Merit',
            'application_start_date' => now()->subDay(),
            'application_deadline'   => now()->addMonth(),
            'status'                 => 'open',
        ]);

        return ScholarshipApplication::create([
            'application_number' => 'SCH-' . date('Y') . '-' . rand(100000, 999999),
            'student_profile_id' => $student->studentProfile->id,
            'scholarship_id'     => $scholarship->id,
            'status'             => ScholarshipApplicationStatus::SUBMITTED->value,
            'submitted_at'       => now(),
        ]);
    }

    // ── Authorization Tests ──────────────────────────

    public function test_guest_cannot_transition(): void
    {
        $app = $this->submittedApplication();
        $this->patch("/admin/scholarship-applications/{$app->id}/transition", [
            'status' => 'under_review',
        ])->assertRedirect('/login');
    }

    public function test_student_cannot_transition(): void
    {
        $student = $this->student();
        $app = $this->submittedApplication($student);

        $this->actingAs($student);
        $this->patch("/admin/scholarship-applications/{$app->id}/transition", [
            'status' => 'under_review',
        ])->assertStatus(403);
    }

    public function test_counselor_cannot_transition(): void
    {
        $app = $this->submittedApplication();

        $this->actingAs($this->counselor());
        $this->patch("/admin/scholarship-applications/{$app->id}/transition", [
            'status' => 'under_review',
        ])->assertStatus(403);
    }

    // ── Valid Transition Tests ───────────────────────

    public function test_admin_can_transition_submitted_to_under_review(): void
    {
        $app = $this->submittedApplication();
        $this->actingAs($this->admin());

        $this->patch("/admin/scholarship-applications/{$app->id}/transition", [
            'status' => 'under_review',
        ])->assertSessionHas('success');

        $this->assertEquals('under_review', $app->fresh()->getRawOriginal('status'));
        $this->assertDatabaseHas('application_status_histories', [
            'scholarship_application_id' => $app->id,
            'previous_status'            => 'submitted',
            'new_status'                 => 'under_review',
        ]);
    }

    // ── Invalid Transition Tests ─────────────────────

    public function test_cannot_skip_submitted_to_approved(): void
    {
        $app = $this->submittedApplication();
        $this->actingAs($this->admin());

        $this->patch("/admin/scholarship-applications/{$app->id}/transition", [
            'status' => 'approved',
        ])->assertSessionHasErrors('transition');

        $this->assertEquals('submitted', $app->fresh()->getRawOriginal('status'));
    }

    public function test_terminal_state_cannot_transition(): void
    {
        $app = $this->submittedApplication();
        $app->update(['status' => ScholarshipApplicationStatus::APPROVED->value]);
        $this->actingAs($this->admin());

        $this->patch("/admin/scholarship-applications/{$app->id}/transition", [
            'status' => 'under_review',
        ])->assertSessionHasErrors('transition');
    }

    // ── Rejection Reason Tests ───────────────────────

    public function test_rejection_requires_remarks(): void
    {
        $app = $this->submittedApplication();
        $app->update(['status' => ScholarshipApplicationStatus::UNDER_REVIEW->value]);
        $this->actingAs($this->admin());

        $this->patch("/admin/scholarship-applications/{$app->id}/transition", [
            'status'  => 'rejected',
            'remarks' => '',
        ])->assertSessionHasErrors('transition');

        $this->assertEquals('under_review', $app->fresh()->getRawOriginal('status'));
    }

    public function test_rejection_succeeds_with_remarks(): void
    {
        $app = $this->submittedApplication();
        $app->update(['status' => ScholarshipApplicationStatus::UNDER_REVIEW->value]);
        $this->actingAs($this->admin());

        $this->patch("/admin/scholarship-applications/{$app->id}/transition", [
            'status'  => 'rejected',
            'remarks' => 'Does not meet eligibility criteria.',
        ])->assertSessionHas('success');

        $this->assertEquals('rejected', $app->fresh()->getRawOriginal('status'));
    }

    // ── Approval Document Verification Tests ─────────

    public function test_approval_blocked_when_required_document_missing(): void
    {
        $app = $this->submittedApplication();
        $app->update(['status' => ScholarshipApplicationStatus::DOCUMENT_VERIFICATION->value]);

        ScholarshipRequirement::create([
            'scholarship_id'   => $app->scholarship_id,
            'requirement_name' => 'TOR',
            'is_required'      => true,
            'display_order'    => 1,
        ]);

        $this->actingAs($this->admin());
        $this->patch("/admin/scholarship-applications/{$app->id}/transition", [
            'status' => 'approved',
        ])->assertSessionHasErrors('transition');

        $this->assertEquals('document_verification', $app->fresh()->getRawOriginal('status'));
    }

    public function test_approval_succeeds_when_all_documents_verified(): void
    {
        $app = $this->submittedApplication();
        $app->update(['status' => ScholarshipApplicationStatus::DOCUMENT_VERIFICATION->value]);

        $req = ScholarshipRequirement::create([
            'scholarship_id'   => $app->scholarship_id,
            'requirement_name' => 'TOR',
            'is_required'      => true,
            'display_order'    => 1,
        ]);

        ApplicationDocument::create([
            'scholarship_application_id' => $app->id,
            'scholarship_requirement_id' => $req->id,
            'file_path'                  => 'test/path.pdf',
            'original_filename'          => 'tor.pdf',
            'verification_status'        => 'verified',
        ]);

        $this->actingAs($this->admin());
        $this->patch("/admin/scholarship-applications/{$app->id}/transition", [
            'status' => 'approved',
        ])->assertSessionHas('success');

        $this->assertEquals('approved', $app->fresh()->getRawOriginal('status'));
    }
}