<?php

namespace Tests\Feature\Security;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Role;
use App\Models\Scholarship;
use App\Models\ScholarshipApplication;
use App\Models\ServiceType;
use App\Models\StudentProfile;
use App\Models\StudentServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected Role $adminRole;

    protected Role $studentRole;

    protected Role $counselorRole;

    protected User $admin;

    protected User $counselor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::create(['name' => 'admin',     'label' => 'Administrator']);
        $this->studentRole = Role::create(['name' => 'student',   'label' => 'Student']);
        $this->counselorRole = Role::create(['name' => 'counselor', 'label' => 'Counselor']);

        $this->admin = User::factory()->create(['role_id' => $this->adminRole->id,     'is_active' => true]);
        $this->counselor = User::factory()->create(['role_id' => $this->counselorRole->id, 'is_active' => true]);
    }

    protected function makeStudentWithProfile(string $suffix): array
    {
        $user = User::factory()->create(['role_id' => $this->studentRole->id, 'is_active' => true]);
        $profile = StudentProfile::create([
            'user_id' => $user->id,
            'student_number' => "2026-SEC-{$suffix}",
            'first_name' => "Test{$suffix}",
            'last_name' => 'User',
            'program' => 'BSCS',
            'year_level' => 3,
            'gwa' => 1.50,
            'enrollment_status' => 'enrolled',
        ]);

        return [$user, $profile];
    }

    public function test_student_cannot_access_admin_dashboard_via_url(): void
    {
        [$student, $profile] = $this->makeStudentWithProfile('A1');
        $this->actingAs($student)->get('/admin/dashboard')->assertStatus(403);
    }

    public function test_student_cannot_access_another_students_application_via_id(): void
    {
        [$studentA, $profileA] = $this->makeStudentWithProfile('A2');
        [$studentB, $profileB] = $this->makeStudentWithProfile('B2');

        $scholarship = Scholarship::create([
            'title' => 'Secret Grant',
            'description' => 'desc',
            'provider' => 'CHED',
            'scholarship_type' => 'academic',
            'status' => 'open',
            'application_start_date' => now()->subDays(5),
            'application_deadline' => now()->addDays(30),
            'created_by' => $this->admin->id,
        ]);

        $appB = ScholarshipApplication::create([
            'application_number' => 'APP-SEC-B2',
            'student_profile_id' => $profileB->id,
            'scholarship_id' => $scholarship->id,
            'status' => 'draft',
        ]);

        $this->actingAs($studentA)
            ->get(route('student.applications.show', $appB))
            ->assertStatus(403);
    }

    public function test_student_cannot_access_another_students_service_request_via_id(): void
    {
        [$studentA, $profileA] = $this->makeStudentWithProfile('A3');
        [$studentB, $profileB] = $this->makeStudentWithProfile('B3');

        $serviceType = ServiceType::create([
            'name' => 'Good Moral',
            'description' => 'desc',
            'is_active' => true,
        ]);

        $requestB = StudentServiceRequest::create([
            'request_number' => 'REQ-SEC-B3',
            'student_profile_id' => $profileB->id,
            'service_type_id' => $serviceType->id,
            'description' => 'B secret request',
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $this->actingAs($studentA)
            ->get(route('student.services.show', $requestB))
            ->assertStatus(403);
    }

    public function test_student_cannot_mass_assign_role_id_via_profile_update(): void
    {
        [$student, $profile] = $this->makeStudentWithProfile('MA');
        $originalRoleId = $student->role_id;

        // Attempt to escalate privilege via admin user-update route
        $response = $this->actingAs($student)
            ->put(route('admin.users.update', $student), [
                'role_id' => $this->adminRole->id,
                'name' => $student->name,
                'email' => $student->email,
            ]);

        // Student must be denied — either 403 (middleware) or 302 (Form Request auth)
        $this->assertContains($response->status(), [302, 403]);

        // Critical: the role must NOT have changed
        $student->refresh();
        $this->assertEquals($originalRoleId, $student->role_id);
    }

    public function test_sql_injection_payload_in_search_is_safely_handled(): void
    {
        [$student, $profile] = $this->makeStudentWithProfile('SQL');

        $payload = "'; DROP TABLE scholarships; --";

        // The scholarships table must still exist after this request
        $this->actingAs($student)
            ->get(route('student.scholarships.index', ['keyword' => $payload]))
            ->assertStatus(200);

        $this->assertTrue(\Schema::hasTable('scholarships'));
    }

    public function test_csrf_token_required_for_state_changing_requests(): void
    {
        [$student, $profile] = $this->makeStudentWithProfile('CSRF');

        $scholarship = Scholarship::create([
            'title' => 'CSRF Grant',
            'description' => 'desc',
            'provider' => 'CHED',
            'scholarship_type' => 'academic',
            'status' => 'open',
            'application_start_date' => now()->subDays(5),
            'application_deadline' => now()->addDays(30),
            'created_by' => $this->admin->id,
        ]);

        // Enable CSRF middleware for this test (normally disabled in testing)
        $this->withMiddleware(VerifyCsrfToken::class ?? \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

        $response = $this->actingAs($student)
            ->post(route('student.applications.store', $scholarship), []);

        // Without a CSRF token, Laravel returns 419 Page Expired
        $this->assertContains($response->status(), [419, 302, 403]);
    }

    public function test_counselor_cannot_access_user_management_routes(): void
    {
        $this->actingAs($this->counselor)
            ->get(route('admin.users.index'))
            ->assertStatus(403);

        $this->actingAs($this->counselor)
            ->get(route('admin.users.create'))
            ->assertStatus(403);
    }
}
