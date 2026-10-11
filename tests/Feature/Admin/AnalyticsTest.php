<?php

namespace Tests\Feature\Admin;

use App\Enums\ScholarshipApplicationStatus;
use App\Models\Role;
use App\Models\Scholarship;
use App\Models\ScholarshipApplication;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $student;
    protected User $counselor;
    protected User $inactiveAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::create(['name' => 'admin', 'label' => 'Administrator']);
        $studentRole = Role::create(['name' => 'student', 'label' => 'Student']);
        $counselorRole = Role::create(['name' => 'counselor', 'label' => 'Counselor']);

        $this->admin = User::factory()->create(['role_id' => $adminRole->id, 'is_active' => true]);
        $this->inactiveAdmin = User::factory()->create(['role_id' => $adminRole->id, 'is_active' => false]);
        $this->student = User::factory()->create(['role_id' => $studentRole->id, 'is_active' => true]);
        $this->counselor = User::factory()->create(['role_id' => $counselorRole->id, 'is_active' => true]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('admin.analytics.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_student_is_denied_analytics_access(): void
    {
        $response = $this->actingAs($this->student)->get(route('admin.analytics.index'));
        $response->assertStatus(403);
    }

    public function test_counselor_is_denied_analytics_access(): void
    {
        $response = $this->actingAs($this->counselor)->get(route('admin.analytics.index'));
        $response->assertStatus(403);
    }

    public function test_inactive_admin_is_denied_access(): void
    {
        $response = $this->actingAs($this->inactiveAdmin)->get(route('admin.analytics.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_active_admin_can_view_analytics_dashboard_and_detail_views(): void
    {
        $this->actingAs($this->admin)->get(route('admin.analytics.index'))->assertOk()->assertSee('Analytics Dashboard');
        $this->actingAs($this->admin)->get(route('admin.analytics.scholarships'))->assertOk()->assertSee('Scholarship Analytics');
        $this->actingAs($this->admin)->get(route('admin.analytics.guidance'))->assertOk()->assertSee('Guidance Analytics');
        $this->actingAs($this->admin)->get(route('admin.analytics.services'))->assertOk()->assertSee('Student Services Analytics');
    }

    public function test_analytics_does_not_expose_student_personal_information(): void
    {
        $profile = StudentProfile::create([
            'user_id' => $this->student->id,
            'student_number' => '2026-SECRET-99',
            'first_name' => 'SecretJane',
            'last_name' => 'PrivateDoe',
            'program' => 'BSCS',
            'year_level' => 3,
            'gwa' => 1.25,
            'family_income' => 150000.00,
            'enrollment_status' => 'enrolled',
        ]);

        $scholarship = Scholarship::create([
            'title' => 'Secret Grant',
            'description' => 'Confidential scholarship description',
            'provider' => 'ISU OSAS',
            'scholarship_type' => 'academic',
            'status' => 'open',
            'application_start_date' => now()->subDays(5),
            'application_deadline' => now()->addDays(5),
            'created_by' => $this->admin->id,
        ]);

        ScholarshipApplication::create([
            'application_number' => 'APP-SECRET-01',
            'student_profile_id' => $profile->id,
            'scholarship_id' => $scholarship->id,
            'status' => ScholarshipApplicationStatus::APPROVED->value,
            'submitted_at' => now(),
            'remarks' => 'Sensitive medical details in internal note',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.analytics.index'));
        $response->assertOk();

        // Must not expose PII
        $response->assertDontSee('SecretJane');
        $response->assertDontSee('PrivateDoe');
        $response->assertDontSee('2026-SECRET-99');
        $response->assertDontSee('Sensitive medical details');
    }

    public function test_active_admin_can_export_aggregate_csv(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.analytics.export'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_student_cannot_export_analytics_csv(): void
    {
        $response = $this->actingAs($this->student)->get(route('admin.analytics.export'));
        $response->assertStatus(403);
    }
}