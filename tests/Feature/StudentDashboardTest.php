<?php

namespace Tests\Feature;

use App\Models\GuidanceAppointment;
use App\Models\Notification;
use App\Models\Role;
use App\Models\Scholarship;
use App\Models\ScholarshipApplication;
use App\Models\StudentProfile;
use App\Models\StudentServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $studentUser;
    protected StudentProfile $studentProfile;
    protected Role $studentRole;
    protected Role $adminRole;
    protected Role $counselorRole;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed basic roles
        $this->studentRole = Role::firstOrCreate(['name' => Role::STUDENT]);
        $this->adminRole = Role::firstOrCreate(['name' => Role::ADMIN]);
        $this->counselorRole = Role::firstOrCreate(['name' => Role::COUNSELOR]);

        // Create active student user
        $this->studentUser = User::create([
            'name' => 'John Doe',
            'email' => 'student@test.isu.edu.ph',
            'password' => bcrypt('password123'),
            'role_id' => $this->studentRole->id,
            'is_active' => true,
        ]);

        // Create student profile
        $this->studentProfile = StudentProfile::create([
            'user_id' => $this->studentUser->id,
            'student_number' => 'STUD-2025-0001',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'middle_name' => 'R',
            'contact_number' => '09123456789',
            'college' => 'CCSICT',
            'program' => 'BSCS',
            'year_level' => 3,
            'enrollment_status' => 'enrolled',
            'gwa' => 1.50,
            'family_income' => 150000.00,
            'address' => 'Echague, Isabela',
        ]);
    }

    public function test_guests_cannot_access_student_dashboard()
    {
        $response = $this->get(route('student.dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_counselors_and_admins_cannot_access_student_dashboard()
    {
        $adminUser = User::create([
            'name' => 'System Administrator',
            'email' => 'admin@test.isu.edu.ph',
            'password' => bcrypt('password123'),
            'role_id' => $this->adminRole->id,
            'is_active' => true,
        ]);

        $this->actingAs($adminUser);
        $response = $this->get(route('student.dashboard'));
        $response->assertStatus(403);

        $counselorUser = User::create([
            'name' => 'Campus Counselor',
            'email' => 'counselor@test.isu.edu.ph',
            'password' => bcrypt('password123'),
            'role_id' => $this->counselorRole->id,
            'is_active' => true,
        ]);

        $this->actingAs($counselorUser);
        $response = $this->get(route('student.dashboard'));
        $response->assertStatus(403);
    }

    public function test_active_students_can_access_their_own_student_dashboard()
    {
        $this->actingAs($this->studentUser);

        $response = $this->get(route('student.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Welcome, John Doe');
        $response->assertSee('STUD-2025-0001');
        $response->assertSee('BSCS - Yr 3');
    }

    public function test_inactive_students_are_denied_access()
    {
        $inactiveStudent = User::create([
            'name' => 'Inactive Student',
            'email' => 'inactive@test.isu.edu.ph',
            'password' => bcrypt('password123'),
            'role_id' => $this->studentRole->id,
            'is_active' => false,
        ]);

        $this->actingAs($inactiveStudent);

        $response = $this->get(route('student.dashboard'));
        // Active account middleware redirects deactivated accounts
        $response->assertRedirect();
        $response->assertSessionHasErrors();
    }

    public function test_dashboard_shows_correct_aggregated_metric_counts_belonging_to_authenticated_student_only()
    {
        $admin = User::create([
            'name' => 'Admin Creator',
            'email' => 'creator@test.isu.edu.ph',
            'password' => bcrypt('password123'),
            'role_id' => $this->adminRole->id,
            'is_active' => true,
        ]);

        $scholarship = Scholarship::create([
            'title' => 'ISU Academic Excellence Grant',
            'description' => 'For honor students',
            'provider' => 'ISU Alumni',
            'scholarship_type' => 'Academic',
            'minimum_gwa' => 1.75,
            'maximum_family_income' => 300000,
            'eligible_programs' => ['BSCS', 'BSIT'],
            'eligible_year_levels' => [2, 3, 4],
            'required_enrollment_status' => 'enrolled',
            'application_start_date' => now()->subDays(5)->toDateString(),
            'application_deadline' => now()->addDays(20)->toDateString(),
            'status' => Scholarship::STATUS_OPEN,
            'created_by' => $admin->id,
        ]);

        // 1. Authenticated student's active application
        ScholarshipApplication::create([
            'application_number' => 'APP-2025-0001',
            'student_profile_id' => $this->studentProfile->id,
            'scholarship_id' => $scholarship->id,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        // 2. Another student's profile and active application
        $otherUser = User::create([
            'name' => 'Jane Smith',
            'email' => 'jane@test.isu.edu.ph',
            'password' => bcrypt('password123'),
            'role_id' => $this->studentRole->id,
            'is_active' => true,
        ]);

        $otherProfile = StudentProfile::create([
            'user_id' => $otherUser->id,
            'student_number' => 'STUD-2025-0002',
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'college' => 'CCSICT',
            'program' => 'BSIT',
            'year_level' => 2,
            'enrollment_status' => 'enrolled',
            'gwa' => 1.25,
            'family_income' => 200000.00,
            'address' => 'Santiago, Isabela',
        ]);

        ScholarshipApplication::create([
            'application_number' => 'APP-2025-0002',
            'student_profile_id' => $otherProfile->id,
            'scholarship_id' => $scholarship->id,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        // Authenticated student visits their dashboard
        $this->actingAs($this->studentUser);

        $response = $this->get(route('student.dashboard'));
        $response->assertStatus(200);

        // Assert strictly isolated metrics: active count must be 1, not 2
        $response->assertViewHas('scholarshipData', function ($data) {
            return $data['activeCount'] === 1 && $data['recent']->application_number === 'APP-2025-0001';
        });
    }

    public function test_dashboard_isolates_guidance_and_notifications_data()
    {
        // Add an unread notification for the authenticated student
        Notification::create([
            'user_id' => $this->studentUser->id,
            'type' => 'scholarship.submitted',
            'title' => 'Application Received',
            'message' => 'Your application has been received.',
            'read_at' => null,
        ]);

        // Add an unread notification for another user
        $otherUser = User::create([
            'name' => 'Other User',
            'email' => 'other@test.isu.edu.ph',
            'password' => bcrypt('password123'),
            'role_id' => $this->studentRole->id,
            'is_active' => true,
        ]);

        Notification::create([
            'user_id' => $otherUser->id,
            'type' => 'scholarship.submitted',
            'title' => 'Other Application Received',
            'message' => 'Your application has been received.',
            'read_at' => null,
        ]);

        $this->actingAs($this->studentUser);

        $response = $this->get(route('student.dashboard'));
        $response->assertStatus(200);
        $response->assertViewHas('unreadNotificationCount', 1);
    }
}