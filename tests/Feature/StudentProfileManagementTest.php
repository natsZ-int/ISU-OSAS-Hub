<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\StudentProfile;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentProfileManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    private function userWithRole(string $role): User
    {
        return User::factory()->create([
            'role_id' => Role::where('name', $role)->firstOrFail()->id,
            'is_active' => true,
        ]);
    }

    private function profileFor(User $user, array $overrides = []): StudentProfile
    {
        return StudentProfile::factory()->create(array_merge([
            'user_id' => $user->id,
            'student_number' => '2026-'.str_pad((string) $user->id, 4, '0', STR_PAD_LEFT),
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'year_level' => 2,
            'enrollment_status' => 'enrolled',
            'gwa' => 1.75,
            'family_income' => 150000,
        ], $overrides));
    }

    /**
     * Payload containing only the fields a student may edit themselves.
     */
    private function studentPayload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Juan',
            'middle_name' => 'Santos',
            'last_name' => 'Dela Cruz',
            'contact_number' => '09171234567',
            'gwa' => 1.50,
            'family_income' => 175000,
            'address' => '123 Sampaguita St., Isabela',
        ], $overrides);
    }

    /**
     * Payload containing every field an administrator may edit.
     */
    private function adminPayload(array $overrides = []): array
    {
        return array_merge($this->studentPayload(), [
            'college' => 'College of Engineering',
            'program' => 'BS Computer Engineering',
            'year_level' => 3,
        ], $overrides);
    }

    public function test_guest_is_redirected_from_student_profile(): void
    {
        $this->get('/student/profile')->assertRedirect('/login');
    }

    public function test_student_can_view_their_own_profile(): void
    {
        $student = $this->userWithRole('student');
        $profile = $this->profileFor($student);

        $this->actingAs($student)
            ->get('/student/profile')
            ->assertOk()
            ->assertSee($profile->student_number)
            ->assertSee('My Profile');
    }

    public function test_student_without_profile_is_redirected_to_dashboard(): void
    {
        $student = $this->userWithRole('student');

        $this->actingAs($student)
            ->get('/student/profile')
            ->assertRedirect(route('student.dashboard'))
            ->assertSessionHas('error');
    }

    public function test_student_can_update_editable_profile_fields(): void
    {
        $student = $this->userWithRole('student');
        $profile = $this->profileFor($student, [
            'college' => 'College of Engineering',
            'program' => 'BS Computer Engineering',
        ]);

        $this->actingAs($student)
            ->put('/student/profile', $this->studentPayload())
            ->assertRedirect(route('student.profile.show'))
            ->assertSessionHas('status');

        $profile->refresh();

        $this->assertSame('Juan', $profile->first_name);
        $this->assertSame('Santos', $profile->middle_name);
        $this->assertSame('College of Engineering', $profile->college);
        $this->assertSame('BS Computer Engineering', $profile->program);
        $this->assertSame(2, $profile->year_level);
        $this->assertSame(1.50, (float) $profile->gwa);
        $this->assertSame(175000.0, (float) $profile->family_income);

        // System-managed fields remain untouched.
        $this->assertSame('2026-'.str_pad((string) $student->id, 4, '0', STR_PAD_LEFT), $profile->student_number);
        $this->assertSame('enrolled', $profile->enrollment_status);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'Student Profile Updated',
            'module' => 'Student Profile',
            'record_type' => 'StudentProfile',
            'record_id' => $profile->id,
        ]);
    }

    public function test_student_can_update_profile_when_readonly_system_fields_are_resubmitted_unchanged(): void
    {
        $student = $this->userWithRole('student');
        $profile = $this->profileFor($student, [
            'college' => 'College of Engineering',
            'program' => 'BS Computer Engineering',
        ]);

        $this->actingAs($student)
            ->put('/student/profile', $this->studentPayload([
                'first_name' => 'Maria',
                'last_name' => 'Reyes',
                'student_number' => $profile->student_number,
                'college' => $profile->college,
                'program' => $profile->program,
                'year_level' => $profile->year_level,
                'enrollment_status' => $profile->enrollment_status,
            ]))
            ->assertRedirect(route('student.profile.show'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');

        $profile->refresh();

        $this->assertSame('Maria', $profile->first_name);
        $this->assertSame('Reyes', $profile->last_name);
        $this->assertSame('2026-'.str_pad((string) $student->id, 4, '0', STR_PAD_LEFT), $profile->student_number);
        $this->assertSame('enrolled', $profile->enrollment_status);
    }

    public function test_student_cannot_change_system_managed_fields(): void
    {
        $student = $this->userWithRole('student');
        $profile = $this->profileFor($student, [
            'college' => 'College of Engineering',
            'program' => 'BS Computer Engineering',
        ]);

        $this->actingAs($student)
            ->put('/student/profile', $this->studentPayload([
                'student_number' => 'HACKED-0001',
                'college' => 'College of Computing Studies',
                'program' => 'BS Information Technology',
                'year_level' => 5,
                'enrollment_status' => 'not_enrolled',
            ]))
            ->assertSessionHasErrors(['student_number', 'college', 'program', 'year_level', 'enrollment_status']);

        $profile->refresh();

        $this->assertNotSame('HACKED-0001', $profile->student_number);
        $this->assertSame('College of Engineering', $profile->college);
        $this->assertSame('BS Computer Engineering', $profile->program);
        $this->assertSame(2, $profile->year_level);
        $this->assertSame('enrolled', $profile->enrollment_status);
    }

    public function test_student_edit_form_marks_academic_information_as_managed_by_osas(): void
    {
        $student = $this->userWithRole('student');
        $this->profileFor($student, [
            'college' => 'College of Engineering',
            'program' => 'BS Computer Engineering',
        ]);

        $this->actingAs($student)
            ->get('/student/profile/edit')
            ->assertOk()
            ->assertSee('College of Engineering')
            ->assertSee('BS Computer Engineering')
            ->assertSee('Year 2')
            ->assertSee('Managed by OSAS. Contact the office to correct it.')
            ->assertDontSee('name="college"', false)
            ->assertDontSee('name="program"', false)
            ->assertDontSee('<select id="year_level"', false);
    }

    public function test_student_profile_update_validates_gwa(): void
    {
        $student = $this->userWithRole('student');
        $this->profileFor($student);

        $this->actingAs($student)
            ->put('/student/profile', $this->studentPayload([
                'gwa' => 6.00,
            ]))
            ->assertSessionHasErrors(['gwa']);
    }

    public function test_student_cannot_access_admin_student_profiles(): void
    {
        $student = $this->userWithRole('student');
        $this->profileFor($student);

        $this->actingAs($student)
            ->get('/admin/student-profiles')
            ->assertStatus(403);
    }

    public function test_counselor_cannot_access_admin_student_profiles(): void
    {
        $counselor = $this->userWithRole('counselor');

        $this->actingAs($counselor)
            ->get('/admin/student-profiles')
            ->assertStatus(403);
    }

    public function test_admin_can_view_student_profiles_index(): void
    {
        $admin = $this->userWithRole('admin');
        $profile = $this->profileFor($this->userWithRole('student'));

        $this->actingAs($admin)
            ->get('/admin/student-profiles')
            ->assertOk()
            ->assertSee($profile->student_number)
            ->assertSee('Dela Cruz');
    }

    public function test_admin_can_filter_student_profiles(): void
    {
        $admin = $this->userWithRole('admin');

        $matching = $this->profileFor($this->userWithRole('student'), [
            'student_number' => '2026-9001',
            'college' => 'College of Computing Studies',
            'program' => 'BSIT',
        ]);
        $other = $this->profileFor($this->userWithRole('student'), [
            'student_number' => '2026-9002',
            'college' => 'College of Engineering',
            'program' => 'BSCE',
        ]);

        $this->actingAs($admin)
            ->get('/admin/student-profiles?college='.urlencode('College of Computing Studies'))
            ->assertOk()
            ->assertSee($matching->student_number)
            ->assertDontSee($other->student_number);
    }

    public function test_admin_can_view_and_update_a_student_profile(): void
    {
        $admin = $this->userWithRole('admin');
        $profile = $this->profileFor($this->userWithRole('student'));

        $this->actingAs($admin)
            ->get('/admin/student-profiles/'.$profile->id)
            ->assertOk()
            ->assertSee($profile->student_number);

        $this->actingAs($admin)
            ->put('/admin/student-profiles/'.$profile->id, $this->adminPayload([
                'student_number' => '2026-9999',
                'enrollment_status' => 'not_enrolled',
                'first_name' => 'Maria',
                'last_name' => 'Santos',
            ]))
            ->assertRedirect(route('admin.student-profiles.show', $profile))
            ->assertSessionHas('status');

        $profile->refresh();

        $this->assertSame('2026-9999', $profile->student_number);
        $this->assertSame('not_enrolled', $profile->enrollment_status);
        $this->assertSame('Maria', $profile->first_name);
        $this->assertSame('Santos', $profile->last_name);
    }

    public function test_admin_update_rejects_duplicate_student_number(): void
    {
        $admin = $this->userWithRole('admin');
        $existing = $this->profileFor($this->userWithRole('student'), ['student_number' => '2026-1111']);
        $profile = $this->profileFor($this->userWithRole('student'), ['student_number' => '2026-2222']);

        $this->actingAs($admin)
            ->put('/admin/student-profiles/'.$profile->id, $this->adminPayload([
                'student_number' => $existing->student_number,
                'enrollment_status' => 'enrolled',
            ]))
            ->assertSessionHasErrors('student_number');

        $this->assertSame('2026-2222', $profile->fresh()->student_number);
    }

    public function test_admin_update_rejects_invalid_enrollment_status(): void
    {
        $admin = $this->userWithRole('admin');
        $profile = $this->profileFor($this->userWithRole('student'));

        $this->actingAs($admin)
            ->put('/admin/student-profiles/'.$profile->id, $this->adminPayload([
                'student_number' => $profile->student_number,
                'enrollment_status' => 'graduated',
            ]))
            ->assertSessionHasErrors('enrollment_status');
    }

    public function test_admin_can_open_edit_form_for_a_student_profile(): void
    {
        $admin = $this->userWithRole('admin');
        $profile = $this->profileFor($this->userWithRole('student'));

        $this->actingAs($admin)
            ->get('/admin/student-profiles/'.$profile->id.'/edit')
            ->assertOk()
            ->assertSee($profile->student_number)
            ->assertSee('Enrollment Status');
    }
}
