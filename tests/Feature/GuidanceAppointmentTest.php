<?php

namespace Tests\Feature;

use App\Models\CounselorAvailability;
use App\Models\GuidanceAppointment;
use App\Models\GuidanceAppointmentType;
use App\Models\GuidanceSchedulingSettings;
use App\Models\Role;
use App\Models\StudentProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuidanceAppointmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $studentUser;

    protected StudentProfile $studentProfile;

    protected User $counselorUser;

    protected GuidanceAppointmentType $type;

    protected GuidanceSchedulingSettings $settings;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure roles exist
        $studentRole = Role::firstOrCreate(['name' => 'student'], ['label' => 'Student']);
        $counselorRole = Role::firstOrCreate(['name' => 'counselor'], ['label' => 'Counselor']);
        Role::firstOrCreate(['name' => 'admin'], ['label' => 'Administrator']);

        // Create student user & profile
        $this->studentUser = User::factory()->create([
            'role_id' => $studentRole->id,
            'is_active' => true,
        ]);

        $this->studentProfile = StudentProfile::create([
            'user_id' => $this->studentUser->id,
            'student_number' => 'ISU-2024-00001',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'college' => 'CCS',
            'program' => 'BSIT',
            'year_level' => 3,
            'enrollment_status' => 'enrolled',
            'gwa' => 1.75,
            'family_income' => 200000,
        ]);

        // Create counselor user
        $this->counselorUser = User::factory()->create([
            'role_id' => $counselorRole->id,
            'is_active' => true,
        ]);

        // Create default appointment type
        $this->type = GuidanceAppointmentType::firstOrCreate(
            ['name' => 'Academic Concern'],
            [
                'duration_minutes' => 30,
                'is_active' => true,
                'display_order' => 1,
            ]
        );

        // Get active scheduling settings
        $this->settings = GuidanceSchedulingSettings::getActive();
    }

    // ── 1. Normal Booking Test ───────────────────────

    public function test_student_can_book_valid_available_slot(): void
    {
        $bookDate = now()->addDays(2)->next(Carbon::MONDAY);
        $dayOfWeek = (int) $bookDate->dayOfWeek;

        // Counselor available on that day 09:00 - 12:00
        CounselorAvailability::create([
            'counselor_id' => $this->counselorUser->id,
            'day_of_week' => $dayOfWeek,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'is_available' => true,
        ]);

        $response = $this->actingAs($this->studentUser)
            ->post(route('student.guidance.appointments.store'), [
                'counselor_id' => $this->counselorUser->id,
                'appointment_type_id' => $this->type->id,
                'appointment_date' => $bookDate->toDateString(),
                'start_time' => '09:30',
                'reason' => 'Need study advice',
            ]);

        $response->assertRedirect(route('student.guidance.appointments'));

        $this->assertDatabaseHas('guidance_appointments', [
            'student_profile_id' => $this->studentProfile->id,
            'counselor_id' => $this->counselorUser->id,
            'start_time' => '09:30:00',
            'end_time' => '10:00:00',
            'status' => 'requested',
        ]);

        $appointment = GuidanceAppointment::where('student_profile_id', $this->studentProfile->id)->first();
        $this->assertNotNull($appointment);
        $this->assertEquals($bookDate->toDateString(), $appointment->appointment_date->toDateString());
    }

    // ── 2. Counselor Double Booking Prevention ──────

    public function test_counselor_double_booking_is_prevented(): void
    {
        $bookDate = now()->addDays(2)->next(Carbon::MONDAY);
        $dayOfWeek = (int) $bookDate->dayOfWeek;

        CounselorAvailability::create([
            'counselor_id' => $this->counselorUser->id,
            'day_of_week' => $dayOfWeek,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'is_available' => true,
        ]);

        // Existing appointment blocks 09:00 to 09:30
        GuidanceAppointment::create([
            'student_profile_id' => $this->studentProfile->id,
            'counselor_id' => $this->counselorUser->id,
            'appointment_type_id' => $this->type->id,
            'appointment_type' => $this->type->name,
            'appointment_date' => $bookDate->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '09:30:00',
            'status' => 'requested',
        ]);

        // Second student attempts to book the exact same slot
        /** @var User $student2 */
        $student2 = User::factory()->create([
            'role_id' => $this->studentUser->role_id,
            'is_active' => true,
        ]);
        StudentProfile::create([
            'user_id' => $student2->id,
            'student_number' => 'ISU-2024-00002',
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'college' => 'CCS',
            'program' => 'BSIT',
            'year_level' => 2,
        ]);

        $response = $this->actingAs($student2)
            ->post(route('student.guidance.appointments.store'), [
                'counselor_id' => $this->counselorUser->id,
                'appointment_type_id' => $this->type->id,
                'appointment_date' => $bookDate->toDateString(),
                'start_time' => '09:00',
            ]);

        $response->assertSessionHasErrors('booking');
        $this->assertDatabaseCount('guidance_appointments', 1);
    }

    // ── 3. Counselor Workflow Transitions ───────────

    public function test_counselor_can_confirm_and_reject_appointments(): void
    {
        $this->withoutExceptionHandling();

        $appointment = GuidanceAppointment::create([
            'student_profile_id' => $this->studentProfile->id,
            'counselor_id' => $this->counselorUser->id,
            'appointment_type_id' => $this->type->id,
            'appointment_type' => $this->type->name,
            'appointment_date' => now()->addDays(2)->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '10:30:00',
            'status' => 'requested',
        ]);

        // Confirm appointment
        $response = $this->actingAs($this->counselorUser)
            ->from(route('counselor.guidance.appointments.show', $appointment))
            ->patch(route('counselor.guidance.appointments.confirm', $appointment));

        $response->assertSessionHasNoErrors();
        $this->assertEquals('confirmed', $appointment->fresh()->status);

        // Cannot directly transition from confirmed to rejected
        $rejectResponse = $this->actingAs($this->counselorUser)
            ->from(route('counselor.guidance.appointments.show', $appointment))
            ->patch(route('counselor.guidance.appointments.reject', $appointment), [
                'rejection_reason' => 'Schedule conflict arose.',
            ]);

        $rejectResponse->assertSessionHasErrors('transition');
        $this->assertEquals('confirmed', $appointment->fresh()->status);
    }

    // ── 4. Student Cancellation Rule ────────────────

    public function test_student_can_cancel_with_adequate_notice(): void
    {
        $appointment = GuidanceAppointment::create([
            'student_profile_id' => $this->studentProfile->id,
            'counselor_id' => $this->counselorUser->id,
            'appointment_type_id' => $this->type->id,
            'appointment_type' => $this->type->name,
            'appointment_date' => now()->addDays(2)->toDateString(),
            'start_time' => '14:00:00',
            'end_time' => '14:30:00',
            'status' => 'requested',
        ]);

        $response = $this->actingAs($this->studentUser)
            ->from(route('student.guidance.appointments.show', $appointment))
            ->patch(route('student.guidance.appointments.cancel', $appointment));

        $response->assertSessionHasNoErrors();
        $this->assertEquals('cancelled', $appointment->fresh()->status);
    }
}
