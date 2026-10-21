<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\ServiceType;
use App\Models\StudentProfile;
use App\Models\StudentServiceRequest;
use App\Models\User;
use App\Services\StudentServiceRequestWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentServicesModuleTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $studentUser;

    protected StudentProfile $studentProfile;

    protected ServiceType $serviceType;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::updateOrCreate(['name' => 'admin'], ['label' => 'Administrator']);
        $studentRole = Role::updateOrCreate(['name' => 'student'], ['label' => 'Student']);

        $this->admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);

        $this->studentUser = User::factory()->create([
            'role_id' => $studentRole->id,
            'is_active' => true,
        ]);

        $this->studentProfile = StudentProfile::factory()->create([
            'user_id' => $this->studentUser->id,
        ]);

        $this->serviceType = ServiceType::create([
            'name' => 'Clearance Sign-off Request',
            'description' => 'Test description',
            'instructions' => 'Test instructions',
            'is_active' => true,
            'display_order' => 1,
        ]);
    }

    public function test_student_can_browse_active_services_catalog()
    {
        $response = $this->actingAs($this->studentUser)
            ->get(route('student.services.catalog'));

        $response->assertStatus(200);
        $response->assertSee('Clearance Sign-off Request');
    }

    public function test_student_can_submit_service_request_and_receives_unique_request_number()
    {
        $response = $this->actingAs($this->studentUser)
            ->post(route('student.services.store'), [
                'service_type_id' => $this->serviceType->id,
                'description' => 'Please sign off my clearance form for graduation requirements.',
            ]);

        $this->assertDatabaseHas('student_service_requests', [
            'student_profile_id' => $this->studentProfile->id,
            'service_type_id' => $this->serviceType->id,
            'status' => 'submitted',
        ]);

        $request = StudentServiceRequest::first();
        $this->assertStringStartsWith('SR-', $request->request_number);
        $this->assertDatabaseHas('student_service_request_histories', [
            'student_service_request_id' => $request->id,
            'new_status' => 'submitted',
        ]);

        $response->assertRedirect(route('student.services.show', $request));
    }

    public function test_required_document_is_enforced_upon_submission()
    {
        $req = $this->serviceType->requirements()->create([
            'name' => 'Scanned Form',
            'is_required' => true,
            'display_order' => 1,
        ]);

        $response = $this->actingAs($this->studentUser)
            ->post(route('student.services.store'), [
                'service_type_id' => $this->serviceType->id,
                'description' => 'Valid length description here.',
            ]);

        $response->assertSessionHasErrors(["requirement_{$req->id}"]);
        $this->assertDatabaseCount('student_service_requests', 0);
    }

    public function test_direct_jump_from_submitted_to_completed_is_rejected_by_fsm()
    {
        $request = StudentServiceRequest::create([
            'request_number' => 'SR-2025-000001',
            'student_profile_id' => $this->studentProfile->id,
            'service_type_id' => $this->serviceType->id,
            'description' => 'Test request description',
            'status' => 'submitted',
        ]);

        $service = app(StudentServiceRequestWorkflowService::class);

        $this->expectException(\InvalidArgumentException::class);
        $service->transition($request, 'completed', $this->admin, 'Attempting direct complete');
    }

    public function test_valid_state_transitions_work_correctly_and_log_history()
    {
        $request = StudentServiceRequest::create([
            'request_number' => 'SR-2025-000002',
            'student_profile_id' => $this->studentProfile->id,
            'service_type_id' => $this->serviceType->id,
            'description' => 'Test request description',
            'status' => 'submitted',
        ]);

        $service = app(StudentServiceRequestWorkflowService::class);

        // submitted -> processing
        $service->transition($request, 'processing', $this->admin);
        $this->assertEquals('processing', $request->fresh()->status);

        // processing -> completed
        $service->transition($request->fresh(), 'completed', $this->admin, 'All good');
        $this->assertEquals('completed', $request->fresh()->status);
        $this->assertNotNull($request->fresh()->completed_at);

        $this->assertDatabaseCount('student_service_request_histories', 2);
    }

    public function test_student_cannot_view_another_students_service_request()
    {
        $otherStudent = User::factory()->create([
            'role_id' => Role::where('name', 'student')->first()->id,
            'is_active' => true,
        ]);
        StudentProfile::factory()->create(['user_id' => $otherStudent->id]);

        $request = StudentServiceRequest::create([
            'request_number' => 'SR-2025-000003',
            'student_profile_id' => $this->studentProfile->id,
            'service_type_id' => $this->serviceType->id,
            'description' => 'Confidential request',
            'status' => 'submitted',
        ]);

        $response = $this->actingAs($otherStudent)
            ->get(route('student.services.show', $request));

        $response->assertStatus(403);
    }
}
