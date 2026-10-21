<?php

namespace Tests\Feature;

use App\Models\CounselorAvailability;
use App\Models\GuidanceAppointment;
use App\Models\Notification;
use App\Models\Role;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CounselorDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $counselorUser;

    protected Role $studentRole;

    protected Role $adminRole;

    protected Role $counselorRole;

    protected StudentProfile $studentProfile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->studentRole = Role::firstOrCreate(['name' => Role::STUDENT]);
        $this->adminRole = Role::firstOrCreate(['name' => Role::ADMIN]);
        $this->counselorRole = Role::firstOrCreate(['name' => Role::COUNSELOR]);

        $this->counselorUser = User::create([
            'name' => 'Dr. Maria Santos',
            'email' => 'counselor@test.isu.edu.ph',
            'password' => bcrypt('password123'),
            'role_id' => $this->counselorRole->id,
            'is_active' => true,
        ]);

        // Create a student for appointment relationships
        $studentUser = User::create([
            'name' => 'Juan Dela Cruz',
            'email' => 'student@test.isu.edu.ph',
            'password' => bcrypt('password123'),
            'role_id' => $this->studentRole->id,
            'is_active' => true,
        ]);

        $this->studentProfile = StudentProfile::create([
            'user_id' => $studentUser->id,
            'student_number' => 'STUD-2025-0001',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'college' => 'CCSICT',
            'program' => 'BSCS',
            'year_level' => 3,
            'enrollment_status' => 'enrolled',
            'gwa' => 1.50,
            'family_income' => 150000.00,
            'address' => 'Echague, Isabela',
        ]);
    }

    // ── Authorization Tests ──

    public function test_guests_cannot_access_counselor_dashboard()
    {
        $response = $this->get(route('counselor.dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_students_cannot_access_counselor_dashboard()
    {
        $student = User::create([
            'name' => 'Student User',
            'email' => 'stu@test.isu.edu.ph',
            'password' => bcrypt('password123'),
            'role_id' => $this->studentRole->id,
            'is_active' => true,
        ]);

        $this->actingAs($student);
        $response = $this->get(route('counselor.dashboard'));
        $response->assertStatus(403);
    }

    public function test_admins_cannot_access_counselor_dashboard()
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'adm@test.isu.edu.ph',
            'password' => bcrypt('password123'),
            'role_id' => $this->adminRole->id,
            'is_active' => true,
        ]);

        $this->actingAs($admin);
        $response = $this->get(route('counselor.dashboard'));
        $response->assertStatus(403);
    }

    public function test_inactive_counselors_are_denied_access()
    {
        $inactiveCounselor = User::create([
            'name' => 'Inactive Counselor',
            'email' => 'inactive-c@test.isu.edu.ph',
            'password' => bcrypt('password123'),
            'role_id' => $this->counselorRole->id,
            'is_active' => false,
        ]);

        $this->actingAs($inactiveCounselor);
        $response = $this->get(route('counselor.dashboard'));
        $response->assertRedirect();
    }

    public function test_active_counselor_can_access_dashboard()
    {
        $this->actingAs($this->counselorUser);

        $response = $this->get(route('counselor.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Welcome, Dr. Maria Santos');
    }

    // ── Data Isolation Tests ──

    public function test_dashboard_shows_only_counselors_own_appointments()
    {
        // Create another counselor
        $otherCounselor = User::create([
            'name' => 'Other Counselor',
            'email' => 'other-c@test.isu.edu.ph',
            'password' => bcrypt('password123'),
            'role_id' => $this->counselorRole->id,
            'is_active' => true,
        ]);

        // Appointment for authenticated counselor
        GuidanceAppointment::create([
            'student_profile_id' => $this->studentProfile->id,
            'counselor_id' => $this->counselorUser->id,
            'appointment_date' => now()->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'appointment_type' => 'Academic',
            'reason' => 'Grade consultation',
            'status' => GuidanceAppointment::STATUS_CONFIRMED,
        ]);

        // Appointment for other counselor
        GuidanceAppointment::create([
            'student_profile_id' => $this->studentProfile->id,
            'counselor_id' => $otherCounselor->id,
            'appointment_date' => now()->toDateString(),
            'start_time' => '11:00:00',
            'end_time' => '12:00:00',
            'appointment_type' => 'Personal',
            'reason' => 'Personal counseling',
            'status' => GuidanceAppointment::STATUS_CONFIRMED,
        ]);

        $this->actingAs($this->counselorUser);

        $response = $this->get(route('counselor.dashboard'));
        $response->assertStatus(200);

        $response->assertViewHas('guidance', function ($data) {
            return $data['today_appointments']->count() === 1;
        });
    }

    public function test_dashboard_isolates_availability_to_authenticated_counselor()
    {
        $otherCounselor = User::create([
            'name' => 'Other Counselor',
            'email' => 'other-c2@test.isu.edu.ph',
            'password' => bcrypt('password123'),
            'role_id' => $this->counselorRole->id,
            'is_active' => true,
        ]);

        CounselorAvailability::create([
            'counselor_id' => $this->counselorUser->id,
            'day_of_week' => 1,
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'is_available' => true,
        ]);

        CounselorAvailability::create([
            'counselor_id' => $otherCounselor->id,
            'day_of_week' => 2,
            'start_time' => '13:00:00',
            'end_time' => '17:00:00',
            'is_available' => true,
        ]);

        $this->actingAs($this->counselorUser);

        $response = $this->get(route('counselor.dashboard'));
        $response->assertStatus(200);

        $response->assertViewHas('guidance', function ($data) {
            return $data['active_availability_count'] === 1;
        });
    }

    public function test_dashboard_isolates_notification_count()
    {
        Notification::create([
            'user_id' => $this->counselorUser->id,
            'type' => 'guidance.appointment_requested',
            'title' => 'New Request',
            'message' => 'A student booked an appointment.',
            'read_at' => null,
        ]);

        $otherUser = User::create([
            'name' => 'Other',
            'email' => 'other-n@test.isu.edu.ph',
            'password' => bcrypt('password123'),
            'role_id' => $this->counselorRole->id,
            'is_active' => true,
        ]);

        Notification::create([
            'user_id' => $otherUser->id,
            'type' => 'guidance.appointment_requested',
            'title' => 'Other Request',
            'message' => 'Another notification.',
            'read_at' => null,
        ]);

        $this->actingAs($this->counselorUser);

        $response = $this->get(route('counselor.dashboard'));
        $response->assertStatus(200);
        $response->assertViewHas('unreadNotificationCount', 1);
    }

    // ── Appointment Filtering Tests ──

    public function test_pending_count_only_includes_requested_status()
    {
        GuidanceAppointment::create([
            'student_profile_id' => $this->studentProfile->id,
            'counselor_id' => $this->counselorUser->id,
            'appointment_date' => now()->addDay()->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'appointment_type' => 'Academic',
            'status' => GuidanceAppointment::STATUS_REQUESTED,
        ]);

        GuidanceAppointment::create([
            'student_profile_id' => $this->studentProfile->id,
            'counselor_id' => $this->counselorUser->id,
            'appointment_date' => now()->addDay()->toDateString(),
            'start_time' => '11:00:00',
            'end_time' => '12:00:00',
            'appointment_type' => 'Personal',
            'status' => GuidanceAppointment::STATUS_CONFIRMED,
        ]);

        $this->actingAs($this->counselorUser);

        $response = $this->get(route('counselor.dashboard'));
        $response->assertStatus(200);

        $response->assertViewHas('guidance', function ($data) {
            return $data['pending_request_count'] === 1;
        });
    }

    public function test_completed_count_only_includes_completed_status()
    {
        GuidanceAppointment::create([
            'student_profile_id' => $this->studentProfile->id,
            'counselor_id' => $this->counselorUser->id,
            'appointment_date' => now()->subDay()->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'appointment_type' => 'Academic',
            'status' => GuidanceAppointment::STATUS_COMPLETED,
        ]);

        GuidanceAppointment::create([
            'student_profile_id' => $this->studentProfile->id,
            'counselor_id' => $this->counselorUser->id,
            'appointment_date' => now()->subDay()->toDateString(),
            'start_time' => '11:00:00',
            'end_time' => '12:00:00',
            'appointment_type' => 'Personal',
            'status' => GuidanceAppointment::STATUS_CANCELLED,
        ]);

        $this->actingAs($this->counselorUser);

        $response = $this->get(route('counselor.dashboard'));
        $response->assertStatus(200);

        $response->assertViewHas('guidance', function ($data) {
            return $data['completed_count'] === 1;
        });
    }

    // ── Empty State Tests ──

    public function test_dashboard_displays_empty_states_when_no_data_exists()
    {
        $this->actingAs($this->counselorUser);

        $response = $this->get(route('counselor.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('No appointments scheduled today.');
        $response->assertSee('No pending appointment requests.');
        $response->assertSee('No upcoming appointments.');
        $response->assertSee('No completed appointments.');
    }
}
