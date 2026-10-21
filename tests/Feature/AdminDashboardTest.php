<?php

namespace Tests\Feature;

use App\Models\GuidanceAppointment;
use App\Models\Role;
use App\Models\Scholarship;
use App\Models\ServiceType;
use App\Models\StudentProfile;
use App\Models\StudentServiceRequest;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $studentUser;

    protected User $counselorUser;

    protected StudentProfile $studentProfile;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::updateOrCreate(['name' => 'admin'], ['label' => 'Administrator']);
        $studentRole = Role::updateOrCreate(['name' => 'student'], ['label' => 'Student']);
        $counselorRole = Role::updateOrCreate(['name' => 'counselor'], ['label' => 'Counselor']);

        $this->admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);

        $this->studentUser = User::factory()->create([
            'role_id' => $studentRole->id,
            'is_active' => true,
        ]);

        $this->counselorUser = User::factory()->create([
            'role_id' => $counselorRole->id,
            'is_active' => true,
        ]);

        // Create student profile
        $this->studentProfile = StudentProfile::factory()->create([
            'user_id' => $this->studentUser->id,
        ]);
    }

    public function test_active_administrator_can_access_dashboard_metrics()
    {
        // 1. Create User metrics data
        User::factory()->count(2)->create([
            'role_id' => Role::where('name', 'student')->first()->id,
            'is_active' => true,
        ]);

        // 2. Create Scholarship metrics data
        Scholarship::create([
            'title' => 'Academic Scholars',
            'description' => 'Academic grant for high-achieving students.',
            'provider' => 'ISU OSAS',
            'scholarship_type' => 'Academic',
            'status' => Scholarship::STATUS_OPEN,
            'created_by' => $this->admin->id,
            'application_start_date' => now()->toDateString(),
            'application_deadline' => now()->addDays(30)->toDateString(),
        ]);

        // 3. Create Service Type and Student Service Request
        $serviceType = ServiceType::create([
            'name' => 'Clearance Signing',
            'description' => 'Clearance assistance',
            'is_active' => true,
            'display_order' => 1,
        ]);

        StudentServiceRequest::create([
            'request_number' => 'SR-2025-000001',
            'student_profile_id' => $this->studentProfile->id,
            'service_type_id' => $serviceType->id,
            'description' => 'Clearance request explanation.',
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        // 4. Create Audit Log
        AuditLogService::log(
            action: 'Dashboard Tested',
            module: 'System',
            description: 'Administrator verified the dashboard.',
            userId: $this->admin->id
        );

        // 5. Create Upcoming Guidance Appointment
        GuidanceAppointment::create([
            'student_profile_id' => $this->studentProfile->id,
            'counselor_id' => $this->counselorUser->id,
            'appointment_date' => now()->addDays(2)->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'appointment_type' => 'academic',
            'reason' => 'Academic counseling request.',
            'status' => GuidanceAppointment::STATUS_CONFIRMED,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Account Metrics');
        $response->assertSee('Scholarship Overview');
        $response->assertSee('Guidance Appointments');
        $response->assertSee('Student Services Queue');
        $response->assertSee('Dashboard Tested');
    }

    public function test_inactive_administrator_is_denied_dashboard_access()
    {
        $this->admin->update(['is_active' => false]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'));

        $response->assertRedirect();
    }

    public function test_student_and_counselor_accounts_cannot_access_admin_dashboard()
    {
        $studentResponse = $this->actingAs($this->studentUser)
            ->get(route('admin.dashboard'));
        $studentResponse->assertStatus(403);

        $counselorResponse = $this->actingAs($this->counselorUser)
            ->get(route('admin.dashboard'));
        $counselorResponse->assertStatus(403);
    }

    public function test_guest_is_redirected_to_login()
    {
        $response = $this->get(route('admin.dashboard'));
        $response->assertRedirect(route('login'));
    }
}
