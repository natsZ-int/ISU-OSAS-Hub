<?php

namespace Tests\Feature\Flow;

use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $student;
    protected User $counselor;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole     = Role::firstOrCreate(['name' => Role::ADMIN], ['label' => 'Administrator']);
        $studentRole   = Role::firstOrCreate(['name' => Role::STUDENT], ['label' => 'Student']);
        $counselorRole = Role::firstOrCreate(['name' => Role::COUNSELOR], ['label' => 'Counselor']);

        $this->admin     = User::factory()->create(['role_id' => $adminRole->id, 'is_active' => true]);
        $this->student   = User::factory()->create(['role_id' => $studentRole->id, 'is_active' => true]);
        $this->counselor = User::factory()->create(['role_id' => $counselorRole->id, 'is_active' => true]);
    }

    public function test_root_redirects_each_role_to_its_own_dashboard(): void
    {
        $this->actingAs($this->admin)->get('/')->assertRedirect(route('admin.dashboard'));
        $this->actingAs($this->counselor)->get('/')->assertRedirect(route('counselor.dashboard'));
        $this->actingAs($this->student)->get('/')->assertRedirect(route('student.dashboard'));
    }

    public function test_root_redirects_guests_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_admin_dashboard_shows_notification_badge_via_view_composer(): void
    {
        // Regression: the composer was never registered and lived in a
        // non-autoloadable namespace, so the badge never rendered.
        Notification::create([
            'user_id' => $this->admin->id,
            'type'    => 'user_management.notice',
            'title'   => 'Test Notice',
            'message' => 'Badge check',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('notification-count', false);
    }

    public function test_audit_module_filter_actually_filters_modules_the_app_writes(): void
    {
        AuditLog::create([
            'user_id'     => $this->admin->id,
            'action'      => 'Application Started',
            'module'      => 'Scholarship Application',
            'record_type' => 'ScholarshipApplication',
            'record_id'   => 1,
            'description' => 'SCHOLARSHIP-FLOW-MARKER',
            'created_at'  => now(),
        ]);
        AuditLog::create([
            'user_id'     => $this->admin->id,
            'action'      => 'User Created',
            'module'      => 'User Management',
            'record_type' => 'User',
            'record_id'   => 2,
            'description' => 'USER-MGMT-MARKER',
            'created_at'  => now(),
        ]);

        // Regression: this module was missing from the whitelist, so the
        // filter silently returned every row.
        $response = $this->actingAs($this->admin)
            ->get(route('admin.audit-logs.index', ['module' => 'Scholarship Application']));

        $response->assertOk();
        $response->assertSee('SCHOLARSHIP-FLOW-MARKER');
        $response->assertDontSee('USER-MGMT-MARKER');
    }

    public function test_student_and_counselor_cannot_access_administration(): void
    {
        $adminRoutes = [
            'admin.dashboard',
            'admin.users.index',
            'admin.scholarships.index',
            'admin.scholarship-applications.index',
            'admin.analytics.index',
            'admin.audit-logs.index',
        ];

        foreach ([$this->student, $this->counselor] as $user) {
            foreach ($adminRoutes as $routeName) {
                $this->actingAs($user)->get(route($routeName))->assertForbidden();
            }
        }
    }

    public function test_deactivated_user_cannot_access_notifications(): void
    {
        $this->student->update(['is_active' => false]);

        $this->actingAs($this->student)
            ->get(route('notifications.index'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_create_a_user_and_the_action_is_audited(): void
    {
        $role = Role::firstOrCreate(['name' => 'counselor'], ['label' => 'Counselor']);

        $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'name'                 => 'New Counselor',
            'email'                => 'counselor.new@isu.local',
            'password'             => 'Secret123!',
            'password_confirmation' => 'Secret123!',
            'role_id'              => $role->id,
        ])->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', ['email' => 'counselor.new@isu.local']);
        $this->assertDatabaseHas('audit_logs', [
            'action'      => 'User Created',
            'module'      => 'User Management',
            'record_type' => 'User',
        ]);
    }
}
