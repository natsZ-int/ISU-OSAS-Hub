<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::updateOrCreate(['name' => 'admin'], ['label' => 'Administrator']);
        $studentRole = Role::updateOrCreate(['name' => 'student'], ['label' => 'Student']);
        Role::updateOrCreate(['name' => 'counselor'], ['label' => 'Counselor']);

        $this->admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);

        $this->student = User::factory()->create([
            'role_id' => $studentRole->id,
            'is_active' => true,
        ]);
    }

    // ── A. Central Audit Service ────────────────────────────

    public function test_audit_service_creates_record_with_all_fields()
    {
        $log = AuditLogService::log(
            action: 'test_action',
            module: 'test_module',
            recordType: 'User',
            recordId: $this->admin->id,
            description: 'Test description.',
            userId: $this->admin->id,
        );

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'test_action',
            'module' => 'test_module',
            'record_type' => 'User',
            'record_id' => $this->admin->id,
            'description' => 'Test description.',
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_audit_service_supports_null_actor_for_system_actions()
    {
        $log = AuditLogService::log(
            action: 'system_cleanup',
            module: 'system',
            description: 'Automated cleanup ran.',
            userId: null,
        );

        $this->assertNull($log->user_id);
        $this->assertEquals('system_cleanup', $log->action);
    }

    // ── B. Authentication Audit ─────────────────────────────

    public function test_successful_login_creates_audit_record()
    {
        $this->post(route('login.store'), [
            'email' => $this->admin->email,
            'password' => 'password',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'login',
            'module' => 'authentication',
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_failed_login_creates_audit_record_without_password()
    {
        $this->post(route('login.store'), [
            'email' => $this->admin->email,
            'password' => 'wrong-password',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'failed_login',
            'module' => 'authentication',
        ]);

        $log = AuditLog::where('action', 'failed_login')->first();
        $this->assertStringNotContainsString('wrong-password', $log->description ?? '');
    }

    public function test_logout_creates_audit_record()
    {
        $this->actingAs($this->admin)
            ->post(route('logout'));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'logout',
            'module' => 'authentication',
            'user_id' => $this->admin->id,
        ]);
    }

    // ── C. Administrator Access ─────────────────────────────

    public function test_admin_can_view_audit_logs()
    {
        AuditLogService::log(
            action: 'test_action',
            module: 'test_module',
            userId: $this->admin->id
        );

        $response = $this->actingAs($this->admin)
            ->get(route('admin.audit-logs.index'));

        $response->assertStatus(200);
        $response->assertSee('test_action');
    }

    public function test_student_cannot_access_audit_logs()
    {
        $response = $this->actingAs($this->student)
            ->get(route('admin.audit-logs.index'));

        $response->assertStatus(403);
    }

    public function test_guest_is_redirected_from_audit_logs()
    {
        $response = $this->get(route('admin.audit-logs.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_admin_can_view_audit_log_detail()
    {
        $log = AuditLogService::log(
            action: 'detail_test',
            module: 'test_module',
            userId: $this->admin->id
        );

        $response = $this->actingAs($this->admin)
            ->get(route('admin.audit-logs.show', $log));

        $response->assertStatus(200);
        $response->assertSee('detail_test');
    }

    // ── D. Search and Filters ───────────────────────────────

    public function test_search_filters_by_description()
    {
        AuditLogService::log('action_a', 'mod', description: 'Unique searchable phrase XYZ', userId: $this->admin->id);
        AuditLogService::log('action_b', 'mod', description: 'Something else entirely', userId: $this->admin->id);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.audit-logs.index', ['search' => 'XYZ']));

        $response->assertStatus(200);
        $response->assertSee('Unique searchable phrase XYZ');
        $response->assertDontSee('Something else entirely');
    }

    public function test_module_filter_works()
    {
        AuditLogService::log('act1', 'authentication', userId: $this->admin->id);
        AuditLogService::log('act2', 'Student Services', userId: $this->admin->id);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.audit-logs.index', ['module' => 'authentication']));

        $response->assertStatus(200);
        $response->assertSee('authentication');
    }

    public function test_date_range_filter_works()
    {
        AuditLogService::log('old_action', 'mod', userId: $this->admin->id);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.audit-logs.index', [
                'date_from' => now()->subDay()->format('Y-m-d'),
                'date_to' => now()->addDay()->format('Y-m-d'),
            ]));

        $response->assertStatus(200);
        $response->assertSee('old_action');
    }

    // ── E. Privacy ──────────────────────────────────────────

    public function test_audit_log_does_not_store_passwords()
    {
        $this->post(route('login.store'), [
            'email' => $this->admin->email,
            'password' => 'SuperSecretPassword123!',
        ]);

        $logs = AuditLog::all();
        foreach ($logs as $log) {
            $this->assertStringNotContainsString('SuperSecretPassword123!', $log->description ?? '');
        }
    }

    // ── F. Append-Only ──────────────────────────────────────

    public function test_no_edit_or_delete_routes_exist()
    {
        $log = AuditLogService::log('immutability_test', 'mod', userId: $this->admin->id);

        $this->actingAs($this->admin);

        $patchResponse = $this->patch("/admin/audit-logs/{$log->id}");
        $this->assertContains($patchResponse->getStatusCode(), [404, 405]);

        $deleteResponse = $this->delete("/admin/audit-logs/{$log->id}");
        $this->assertContains($deleteResponse->getStatusCode(), [404, 405]);
    }
}
