<?php

namespace Tests\Feature\Flow;

use App\Models\CounselorAvailability;
use App\Models\GuidanceAppointment;
use App\Models\GuidanceAppointmentType;
use App\Models\Role;
use App\Models\StudentProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuidanceAppointmentFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $studentA;
    protected User $studentB;
    protected User $counselor1;
    protected User $counselor2;
    protected StudentProfile $profileA;
    protected GuidanceAppointmentType $type;
    protected Carbon $bookDate;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole     = Role::firstOrCreate(['name' => Role::ADMIN], ['label' => 'Administrator']);
        $studentRole   = Role::firstOrCreate(['name' => Role::STUDENT], ['label' => 'Student']);
        $counselorRole = Role::firstOrCreate(['name' => Role::COUNSELOR], ['label' => 'Counselor']);

        $this->admin     = User::factory()->create(['role_id' => $adminRole->id, 'is_active' => true]);
        $this->studentA  = User::factory()->create(['role_id' => $studentRole->id, 'is_active' => true]);
        $this->studentB  = User::factory()->create(['role_id' => $studentRole->id, 'is_active' => true]);
        $this->counselor1 = User::factory()->create(['role_id' => $counselorRole->id, 'is_active' => true]);
        $this->counselor2 = User::factory()->create(['role_id' => $counselorRole->id, 'is_active' => true]);

        $this->profileA = StudentProfile::factory()->create(['user_id' => $this->studentA->id]);
        StudentProfile::factory()->create(['user_id' => $this->studentB->id]);

        $this->type = GuidanceAppointmentType::firstOrCreate(
            ['name' => 'Academic Consultation'],
            ['duration_minutes' => 30, 'is_active' => true, 'display_order' => 1]
        );

        $this->bookDate = now()->addDays(2)->next(Carbon::MONDAY);

        foreach ([$this->counselor1, $this->counselor2] as $counselor) {
            CounselorAvailability::create([
                'counselor_id' => $counselor->id,
                'day_of_week'  => (int) $this->bookDate->dayOfWeek,
                'start_time'   => '09:00:00',
                'end_time'     => '12:00:00',
                'is_available' => true,
            ]);
        }
    }

    protected function book(User $student, User $counselor, string $time = '09:30'): GuidanceAppointment
    {
        $this->actingAs($student)->post(route('student.guidance.appointments.store'), [
            'counselor_id'        => $counselor->id,
            'appointment_type_id' => $this->type->id,
            'appointment_date'    => $this->bookDate->toDateString(),
            'start_time'          => $time,
            'reason'              => 'Integration flow booking',
        ]);

        return GuidanceAppointment::where('counselor_id', $counselor->id)
            ->where('start_time', $time . ':00')
            ->firstOrFail();
    }

    public function test_booking_creates_appointment_audit_and_enum_typed_notification(): void
    {
        $appointment = $this->book($this->studentA, $this->counselor1);

        $this->assertEquals('requested', $appointment->getRawOriginal('status'));

        $this->assertDatabaseHas('audit_logs', [
            'action'      => 'Appointment Requested',
            'record_type' => 'GuidanceAppointment',
            'record_id'   => $appointment->id,
        ]);

        // Regression: booking notification must use the enum type, not a raw string.
        $this->assertDatabaseHas('notifications', [
            'user_id'      => $this->counselor1->id,
            'type'         => 'guidance.appointment_requested',
            'related_type' => 'GuidanceAppointment',
            'related_id'   => $appointment->id,
        ]);
    }

    public function test_counselor_notification_links_point_to_counselor_route(): void
    {
        $appointment = $this->book($this->studentA, $this->counselor1);

        $response = $this->actingAs($this->counselor1)
            ->get(route('notifications.index'));

        $response->assertOk();
        $response->assertSee('/counselor/guidance/appointments/' . $appointment->id, false);
        $response->assertDontSee('/student/guidance/appointments/' . $appointment->id, false);
    }

    public function test_another_counselor_cannot_confirm_someone_elses_appointment(): void
    {
        $appointment = $this->book($this->studentA, $this->counselor1);

        $this->actingAs($this->counselor2)
            ->patch(route('counselor.guidance.appointments.confirm', $appointment))
            ->assertForbidden();

        $this->assertEquals('requested', $appointment->fresh()->getRawOriginal('status'));
    }

    public function test_student_cannot_view_or_cancel_another_students_appointment(): void
    {
        $appointment = $this->book($this->studentA, $this->counselor1);

        $this->actingAs($this->studentB)
            ->get(route('student.guidance.appointments.show', $appointment))
            ->assertForbidden();

        $this->actingAs($this->studentB)
            ->patch(route('student.guidance.appointments.cancel', $appointment))
            ->assertForbidden();

        $this->assertEquals('requested', $appointment->fresh()->getRawOriginal('status'));
    }

    public function test_assigned_counselor_can_reject_with_reason_and_student_is_notified(): void
    {
        $appointment = $this->book($this->studentA, $this->counselor1);

        $this->actingAs($this->counselor1)
            ->patch(route('counselor.guidance.appointments.reject', $appointment), [
                'rejection_reason' => 'Counselor is unavailable on this date.',
            ])
            ->assertRedirect();

        $this->assertEquals('rejected', $appointment->fresh()->getRawOriginal('status'));
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->studentA->id,
            'type'    => 'guidance.appointment_rejected',
        ]);
    }

    public function test_student_cancellation_notifies_counselor(): void
    {
        $appointment = $this->book($this->studentA, $this->counselor1);

        $this->actingAs($this->studentA)
            ->patch(route('student.guidance.appointments.cancel', $appointment))
            ->assertRedirect();

        $this->assertEquals('cancelled', $appointment->fresh()->getRawOriginal('status'));
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->counselor1->id,
            'type'    => 'guidance.appointment_cancelled',
        ]);
    }

    public function test_booking_with_a_deactivated_counselor_is_rejected(): void
    {
        $this->counselor1->update(['is_active' => false]);

        $this->actingAs($this->studentA)
            ->post(route('student.guidance.appointments.store'), [
                'counselor_id'        => $this->counselor1->id,
                'appointment_type_id' => $this->type->id,
                'appointment_date'    => $this->bookDate->toDateString(),
                'start_time'          => '09:30',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('guidance_appointments', 0);
    }

    public function test_slots_page_honors_settings_based_booking_window(): void
    {
        $settings = \App\Models\GuidanceSchedulingSettings::getActive();
        $settings->update(['maximum_future_booking_days' => 60]);

        $response = $this->actingAs($this->studentA)
            ->get(route('student.guidance.slots', $this->counselor1));

        $response->assertOk();
        $response->assertSee(now()->addDays(60)->toDateString(), false);
    }
}
