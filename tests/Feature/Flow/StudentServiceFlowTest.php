<?php

namespace Tests\Feature\Flow;

use App\Models\Role;
use App\Models\ServiceType;
use App\Models\StudentProfile;
use App\Models\StudentServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class StudentServiceFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $studentA;

    protected User $studentB;

    protected User $handler;

    protected StudentProfile $profileA;

    protected ServiceType $serviceType;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => Role::ADMIN], ['label' => 'Administrator']);
        $studentRole = Role::firstOrCreate(['name' => Role::STUDENT], ['label' => 'Student']);
        $counselorRole = Role::firstOrCreate(['name' => Role::COUNSELOR], ['label' => 'Counselor']);

        $this->admin = User::factory()->create(['role_id' => $adminRole->id, 'is_active' => true]);
        $this->studentA = User::factory()->create(['role_id' => $studentRole->id, 'is_active' => true]);
        $this->studentB = User::factory()->create(['role_id' => $studentRole->id, 'is_active' => true]);
        $this->handler = User::factory()->create(['role_id' => $counselorRole->id, 'is_active' => true]);

        $this->profileA = StudentProfile::factory()->create(['user_id' => $this->studentA->id]);
        StudentProfile::factory()->create(['user_id' => $this->studentB->id]);

        $this->serviceType = ServiceType::create([
            'name' => 'Good Moral Request',
            'description' => 'Integration flow service',
            'is_active' => true,
            'display_order' => 1,
        ]);
    }

    protected function submitRequest(): StudentServiceRequest
    {
        $this->actingAs($this->studentA)->post(route('student.services.store'), [
            'service_type_id' => $this->serviceType->id,
            'description' => 'Please process my good moral request.',
        ]);

        return StudentServiceRequest::where('student_profile_id', $this->profileA->id)->firstOrFail();
    }

    public function test_submission_creates_request_history_and_audit(): void
    {
        $request = $this->submitRequest();

        $this->assertEquals('submitted', $request->getRawOriginal('status'));
        $this->assertStringStartsWith('SR-', $request->request_number);
        $this->assertDatabaseHas('student_service_request_histories', [
            'student_service_request_id' => $request->id,
            'new_status' => 'submitted',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'Submitted Service Request',
            'record_type' => 'StudentServiceRequest',
            'record_id' => $request->id,
        ]);
    }

    public function test_admin_can_assign_handler_and_assign_ability_is_granted(): void
    {
        $request = $this->submitRequest();

        $this->actingAs($this->admin)
            ->patch(route('admin.student-service-requests.assign', $request), [
                'handled_by' => $this->handler->id,
            ])
            ->assertRedirect();

        $this->assertEquals($this->handler->id, $request->fresh()->handled_by);
        $this->assertEquals('processing', $request->fresh()->getRawOriginal('status'));

        // Regression: the admin "Assign Handler" UI was dead because the policy
        // ability was defined only on a duplicate, non-loaded class.
        $this->assertTrue(Gate::forUser($this->admin)->allows('assign', $request));
        $this->assertFalse(Gate::forUser($this->studentA)->allows('assign', $request));
    }

    public function test_requires_information_requires_explanation_and_notifies_student(): void
    {
        $request = $this->submitRequest();

        $this->actingAs($this->admin)
            ->patch(route('admin.student-service-requests.transition', $request), [
                'status' => 'requires_information',
                'remarks' => '',
            ])
            ->assertSessionHas('error');

        $this->actingAs($this->admin)
            ->patch(route('admin.student-service-requests.transition', $request), [
                'status' => 'requires_information',
                'remarks' => 'Please upload a clearer scanned copy.',
            ])
            ->assertSessionHas('status');

        $this->assertEquals('requires_information', $request->fresh()->getRawOriginal('status'));
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->studentA->id,
            'type' => 'student_services.additional_information_required',
        ]);
    }

    public function test_completion_requires_remarks(): void
    {
        $request = $this->submitRequest();

        $this->actingAs($this->admin)
            ->patch(route('admin.student-service-requests.transition', $request), [
                'status' => 'processing',
            ]);

        $this->actingAs($this->admin)
            ->patch(route('admin.student-service-requests.transition', $request), [
                'status' => 'completed',
                'remarks' => '',
            ])
            ->assertSessionHas('error');

        $this->actingAs($this->admin)
            ->patch(route('admin.student-service-requests.transition', $request), [
                'status' => 'completed',
                'remarks' => 'Released to student.',
            ]);

        $this->assertEquals('completed', $request->fresh()->getRawOriginal('status'));
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->studentA->id,
            'type' => 'student_services.request_completed',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'record_type' => 'StudentServiceRequest',
            'record_id' => $request->id,
        ]);
    }

    public function test_student_can_cancel_own_request(): void
    {
        $request = $this->submitRequest();

        $this->actingAs($this->studentA)
            ->patch(route('student.services.cancel', $request))
            ->assertSessionHas('status');

        $this->assertEquals('cancelled', $request->fresh()->getRawOriginal('status'));
    }
}
