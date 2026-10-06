<?php

namespace Tests\Feature;

use App\Enums\NotificationType;
use App\Models\Notification;
use App\Models\Role;
use App\Models\StudentProfile;
use App\Models\StudentServiceRequest;
use App\Models\ServiceType;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\StudentServiceRequestWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $studentUser;
    protected StudentProfile $studentProfile;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::updateOrCreate(['name' => 'admin'], ['label' => 'Administrator']);
        $studentRole = Role::updateOrCreate(['name' => 'student'], ['label' => 'Student']);

        $this->admin = User::factory()->create([
            'role_id'   => $adminRole->id,
            'is_active' => true,
        ]);

        $this->studentUser = User::factory()->create([
            'role_id'   => $studentRole->id,
            'is_active' => true,
        ]);

        $this->studentProfile = StudentProfile::factory()->create([
            'user_id' => $this->studentUser->id,
        ]);
    }

    // ── A. Notification Creation ────────────────────────────

    public function test_notification_service_creates_notification_for_user()
    {
        $service = app(NotificationService::class);

        $notification = $service->notifyUser(
            $this->studentUser,
            NotificationType::SCHOLARSHIP_APPLICATION_APPROVED,
            'Application Approved',
            'Your scholarship application was approved.',
            'ScholarshipApplication',
            1
        );

        $this->assertNotNull($notification);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->studentUser->id,
            'type'    => 'scholarship.application_approved',
            'title'   => 'Application Approved',
        ]);
        $this->assertNull($notification->read_at);
    }

    public function test_duplicate_notifications_are_prevented()
    {
        $service = app(NotificationService::class);

        $service->notifyUser(
            $this->studentUser,
            NotificationType::GUIDANCE_APPOINTMENT_CONFIRMED,
            'Confirmed',
            'Your appointment is confirmed.',
            'GuidanceAppointment',
            5
        );

        // Same type + related record should be skipped
        $duplicate = $service->notifyUser(
            $this->studentUser,
            NotificationType::GUIDANCE_APPOINTMENT_CONFIRMED,
            'Confirmed',
            'Your appointment is confirmed.',
            'GuidanceAppointment',
            5
        );

        $this->assertNull($duplicate);
        $this->assertEquals(1, Notification::where('user_id', $this->studentUser->id)->count());
    }

    public function test_inactive_user_does_not_receive_notification()
    {
        $this->studentUser->update(['is_active' => false]);

        $service = app(NotificationService::class);
        $result = $service->notifyUser(
            $this->studentUser,
            NotificationType::STUDENT_SERVICE_REQUEST_COMPLETED,
            'Completed',
            'Done.',
        );

        $this->assertNull($result);
        $this->assertDatabaseCount('notifications', 0);
    }

    // ── B. Unread Count ─────────────────────────────────────

    public function test_unread_count_is_accurate()
    {
        $service = app(NotificationService::class);

        $service->notifyUser($this->studentUser, NotificationType::SCHOLARSHIP_APPLICATION_APPROVED, 'T1', 'M1');
        $service->notifyUser($this->studentUser, NotificationType::SCHOLARSHIP_APPLICATION_REJECTED, 'T2', 'M2');
        $service->notifyUser($this->studentUser, NotificationType::GUIDANCE_APPOINTMENT_CONFIRMED, 'T3', 'M3');

        // Mark one as read
        Notification::forUser($this->studentUser->id)->first()->update(['read_at' => now()]);

        $this->assertEquals(2, $service->getUnreadCount($this->studentUser));
    }

    public function test_user_cannot_see_another_users_unread_count()
    {
        $service = app(NotificationService::class);

        $service->notifyUser($this->studentUser, NotificationType::SCHOLARSHIP_APPLICATION_APPROVED, 'T1', 'M1');

        $this->actingAs($this->admin);
        $response = $this->get(route('notifications.index'));

        $response->assertStatus(200);
        $response->assertSee('0 unread');
    }

    // ── C. Mark One as Read ─────────────────────────────────

    public function test_user_can_mark_own_notification_as_read()
    {
        $notification = Notification::create([
            'user_id' => $this->studentUser->id,
            'type'    => 'scholarship.application_approved',
            'title'   => 'Approved',
            'message' => 'Your application was approved.',
        ]);

        $this->actingAs($this->studentUser)
            ->patch(route('notifications.read', $notification));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_cannot_mark_another_users_notification_as_read()
    {
        $notification = Notification::create([
            'user_id' => $this->admin->id,
            'type'    => 'student_services.request_submitted',
            'title'   => 'New Request',
            'message' => 'A student submitted a request.',
        ]);

        $response = $this->actingAs($this->studentUser)
            ->patch(route('notifications.read', $notification));

        $response->assertStatus(403);
        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_mark_as_read_is_idempotent()
    {
        $notification = Notification::create([
            'user_id' => $this->studentUser->id,
            'type'    => 'guidance.appointment_confirmed',
            'title'   => 'Confirmed',
            'message' => 'Confirmed.',
        ]);

        $this->actingAs($this->studentUser)
            ->patch(route('notifications.read', $notification));
        $this->actingAs($this->studentUser)
            ->patch(route('notifications.read', $notification));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    // ── D. Mark All as Read ─────────────────────────────────

    public function test_mark_all_as_read_only_affects_current_user()
    {
        Notification::create(['user_id' => $this->studentUser->id, 'type' => 't1', 'title' => 'T1', 'message' => 'M1']);
        Notification::create(['user_id' => $this->studentUser->id, 'type' => 't2', 'title' => 'T2', 'message' => 'M2']);
        Notification::create(['user_id' => $this->admin->id, 'type' => 't3', 'title' => 'T3', 'message' => 'M3']);

        $this->actingAs($this->studentUser)
            ->patch(route('notifications.read-all'));

        $this->assertEquals(0, Notification::forUser($this->studentUser->id)->unread()->count());
        $this->assertEquals(1, Notification::forUser($this->admin->id)->unread()->count());
    }

    // ── E. Notification List Authorization ──────────────────

    public function test_user_sees_only_own_notifications()
    {
        Notification::create(['user_id' => $this->studentUser->id, 'type' => 't1', 'title' => 'Mine', 'message' => 'M']);
        Notification::create(['user_id' => $this->admin->id, 'type' => 't2', 'title' => 'Admin Only', 'message' => 'M']);

        $response = $this->actingAs($this->studentUser)
            ->get(route('notifications.index'));

        $response->assertStatus(200);
        $response->assertSee('Mine');
        $response->assertDontSee('Admin Only');
    }

    public function test_guest_is_redirected_from_notifications()
    {
        $response = $this->get(route('notifications.index'));
        $response->assertRedirect(route('login'));
    }

    // ── F. Workflow Integration ─────────────────────────────

    public function test_service_request_transition_creates_notification()
    {
        $serviceType = ServiceType::create([
            'name' => 'Test Service', 'is_active' => true, 'display_order' => 1,
        ]);

        $request = StudentServiceRequest::create([
            'request_number'     => 'SR-2025-000099',
            'student_profile_id' => $this->studentProfile->id,
            'service_type_id'    => $serviceType->id,
            'description'        => 'Test request',
            'status'             => 'submitted',
        ]);

        $workflowService = app(StudentServiceRequestWorkflowService::class);
        $workflowService->transition($request, 'processing', $this->admin);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->studentUser->id,
            'type'    => 'student_services.request_processing',
        ]);
    }

    public function test_invalid_transition_does_not_create_notification()
    {
        $serviceType = ServiceType::create([
            'name' => 'Test Service 2', 'is_active' => true, 'display_order' => 2,
        ]);

        $request = StudentServiceRequest::create([
            'request_number'     => 'SR-2025-000098',
            'student_profile_id' => $this->studentProfile->id,
            'service_type_id'    => $serviceType->id,
            'description'        => 'Test request',
            'status'             => 'submitted',
        ]);

        $workflowService = app(StudentServiceRequestWorkflowService::class);

        try {
            $workflowService->transition($request, 'completed', $this->admin, 'Direct complete');
        } catch (\InvalidArgumentException) {
            // Expected
        }

        $this->assertDatabaseMissing('notifications', [
            'user_id' => $this->studentUser->id,
            'type'    => 'student_services.request_completed',
        ]);
    }
}