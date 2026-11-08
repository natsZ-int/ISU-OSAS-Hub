<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\StudentEnrollmentVerification;
use App\Models\StudentProfile;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    private function createAdmin(): User
    {
        $user = User::create([
            'name' => 'OSAS Admin',
            'email' => 'osas.admin@test.local',
            'password' => 'secret-password',
            'role_id' => Role::where('name', Role::ADMIN)->first()->id,
            'is_active' => true,
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        return $user;
    }

    private function createCounselor(): User
    {
        $user = User::create([
            'name' => 'OSAS Counselor',
            'email' => 'osas.counselor@test.local',
            'password' => 'secret-password',
            'role_id' => Role::where('name', Role::COUNSELOR)->first()->id,
            'is_active' => true,
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        return $user;
    }

    private function createVerifiedStudentWithPendingVerification(): User
    {
        $user = User::create([
            'name' => 'Verify Me',
            'email' => 'verifyme@test.local',
            'password' => 'secret-password',
            'role_id' => Role::where('name', Role::STUDENT)->first()->id,
            'is_active' => true,
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        $profile = StudentProfile::create([
            'user_id' => $user->id,
            'student_number' => '2026-3001',
            'first_name' => 'Verify',
            'last_name' => 'Me',
            'year_level' => 1,
        ]);

        StudentEnrollmentVerification::create([
            'student_profile_id' => $profile->id,
            'academic_year' => config('academic.current_year'),
            'semester' => config('academic.current_semester'),
            'status' => 'pending',
        ]);

        return $user;
    }

    public function test_guest_cannot_access_verification_queue(): void
    {
        $this->get('/enrollment-verifications')->assertRedirect('/login');
    }

    public function test_student_cannot_access_verification_queue(): void
    {
        $student = $this->createVerifiedStudentWithPendingVerification();

        $this->actingAs($student)
            ->get('/enrollment-verifications')
            ->assertStatus(403);
    }

    public function test_admin_can_access_verification_queue(): void
    {
        $this->actingAs($this->createAdmin())
            ->get('/enrollment-verifications')
            ->assertOk()
            ->assertSee('Enrollment Verification Queue');
    }

    public function test_counselor_can_access_verification_queue(): void
    {
        $this->actingAs($this->createCounselor())
            ->get('/enrollment-verifications')
            ->assertOk();
    }

    public function test_queue_can_be_searched_and_filtered(): void
    {
        $student = $this->createVerifiedStudentWithPendingVerification();
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->get('/enrollment-verifications?search=2026-3001')
            ->assertOk()
            ->assertSee('2026-3001');

        $this->actingAs($admin)
            ->get('/enrollment-verifications?status=verified')
            ->assertOk()
            ->assertDontSee('2026-3001');
    }

    public function test_staff_can_verify_enrollment(): void
    {
        $student = $this->createVerifiedStudentWithPendingVerification();
        $verif = $student->studentProfile->enrollmentVerifications()->first();
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->patch("/enrollment-verifications/{$verif->id}", [
                'status' => 'verified',
                'academic_year' => config('academic.current_year'),
                'semester' => config('academic.current_semester'),
                'notes' => 'Checked against registrar list.',
            ])
            ->assertRedirect(route('enrollment-verifications.index'));

        $verif->refresh();
        $this->assertSame('verified', $verif->status);
        $this->assertSame($admin->id, $verif->verified_by);
        $this->assertNotNull($verif->verified_at);
        $this->assertSame('enrolled', $student->studentProfile->fresh()->enrollment_status);
        $this->assertTrue($student->fresh()->hasVerifiedEnrollmentForCurrentTerm());
    }

    public function test_staff_can_reject_with_required_reason(): void
    {
        $student = $this->createVerifiedStudentWithPendingVerification();
        $verif = $student->studentProfile->enrollmentVerifications()->first();

        $this->actingAs($this->createAdmin())
            ->patch("/enrollment-verifications/{$verif->id}", [
                'status' => 'rejected',
                'academic_year' => config('academic.current_year'),
                'semester' => config('academic.current_semester'),
                'notes' => 'Not on the current registrar list.',
            ])
            ->assertRedirect(route('enrollment-verifications.index'));

        $this->assertSame('rejected', $verif->fresh()->status);
        $this->assertSame('not_enrolled', $student->studentProfile->fresh()->enrollment_status);
        $this->assertFalse($student->fresh()->hasVerifiedEnrollmentForCurrentTerm());
    }

    public function test_rejection_requires_a_reason(): void
    {
        $student = $this->createVerifiedStudentWithPendingVerification();
        $verif = $student->studentProfile->enrollmentVerifications()->first();

        $this->actingAs($this->createAdmin())
            ->from("/enrollment-verifications/{$verif->id}")
            ->patch("/enrollment-verifications/{$verif->id}", [
                'status' => 'rejected',
                'academic_year' => config('academic.current_year'),
                'semester' => config('academic.current_semester'),
            ])
            ->assertSessionHasErrors('notes');

        $this->assertSame('pending', $verif->fresh()->status);
    }

    public function test_unable_to_verify_requires_a_reason(): void
    {
        $student = $this->createVerifiedStudentWithPendingVerification();
        $verif = $student->studentProfile->enrollmentVerifications()->first();

        $this->actingAs($this->createAdmin())
            ->from("/enrollment-verifications/{$verif->id}")
            ->patch("/enrollment-verifications/{$verif->id}", [
                'status' => 'unable_to_verify',
                'academic_year' => config('academic.current_year'),
                'semester' => config('academic.current_semester'),
            ])
            ->assertSessionHasErrors('notes');
    }

    public function test_student_cannot_approve_their_own_verification(): void
    {
        $student = $this->createVerifiedStudentWithPendingVerification();
        $verif = $student->studentProfile->enrollmentVerifications()->first();

        $this->actingAs($student)
            ->patch("/enrollment-verifications/{$verif->id}", [
                'status' => 'verified',
                'academic_year' => config('academic.current_year'),
                'semester' => config('academic.current_semester'),
            ])
            ->assertStatus(403);

        $this->assertSame('pending', $verif->fresh()->status);
    }

    public function test_counselor_can_record_a_decision(): void
    {
        $student = $this->createVerifiedStudentWithPendingVerification();
        $verif = $student->studentProfile->enrollmentVerifications()->first();
        $counselor = $this->createCounselor();

        $this->actingAs($counselor)
            ->patch("/enrollment-verifications/{$verif->id}", [
                'status' => 'verified',
                'academic_year' => config('academic.current_year'),
                'semester' => config('academic.current_semester'),
            ])
            ->assertRedirect(route('enrollment-verifications.index'));

        $this->assertSame($counselor->id, $verif->fresh()->verified_by);
    }

    public function test_verification_records_academic_term(): void
    {
        $student = $this->createVerifiedStudentWithPendingVerification();
        $verif = $student->studentProfile->enrollmentVerifications()->first();

        $this->actingAs($this->createAdmin())
            ->patch("/enrollment-verifications/{$verif->id}", [
                'status' => 'verified',
                'academic_year' => '2024-2025',
                'semester' => 'second',
            ]);

        $verif->refresh();
        $this->assertSame('2024-2025', $verif->academic_year);
        $this->assertSame('second', $verif->semester);
    }

    public function test_verification_for_previous_term_does_not_grant_current_term_access(): void
    {
        $student = $this->createVerifiedStudentWithPendingVerification();
        $verif = $student->studentProfile->enrollmentVerifications()->first();

        $this->actingAs($this->createAdmin())
            ->patch("/enrollment-verifications/{$verif->id}", [
                'status' => 'verified',
                'academic_year' => '2024-2025',
                'semester' => 'second',
            ]);

        $this->assertFalse($student->fresh()->hasVerifiedEnrollmentForCurrentTerm());

        $this->actingAs($student->fresh())
            ->from('/student/dashboard')
            ->post('/student/service-requests')
            ->assertRedirect(route('student.dashboard'))
            ->assertSessionHas('error');
    }

    public function test_verified_current_term_enrollment_grants_access_to_restricted_routes(): void
    {
        $student = $this->createVerifiedStudentWithPendingVerification();
        $verif = $student->studentProfile->enrollmentVerifications()->first();

        $this->actingAs($this->createAdmin())
            ->patch("/enrollment-verifications/{$verif->id}", [
                'status' => 'verified',
                'academic_year' => config('academic.current_year'),
                'semester' => config('academic.current_semester'),
            ]);

        $this->actingAs($student->fresh())
            ->post('/student/service-requests', [])
            ->assertSessionMissing('error');
    }

    public function test_verification_history_is_visible_to_staff(): void
    {
        $student = $this->createVerifiedStudentWithPendingVerification();
        $verif = $student->studentProfile->enrollmentVerifications()->first();

        $this->actingAs($this->createAdmin())
            ->get("/enrollment-verifications/{$verif->id}")
            ->assertOk()
            ->assertSee('Verification History')
            ->assertSee('2026-3001');
    }

    public function test_legacy_students_without_verification_records_keep_access(): void
    {
        $user = User::create([
            'name' => 'Legacy Student',
            'email' => 'legacy@test.local',
            'password' => 'secret-password',
            'role_id' => Role::where('name', Role::STUDENT)->first()->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        StudentProfile::create([
            'user_id' => $user->id,
            'student_number' => '2020-0001',
            'first_name' => 'Legacy',
            'last_name' => 'Student',
            'year_level' => 1,
        ]);

        $this->actingAs($user)
            ->post('/student/service-requests', [])
            ->assertSessionMissing('error');
    }
}
