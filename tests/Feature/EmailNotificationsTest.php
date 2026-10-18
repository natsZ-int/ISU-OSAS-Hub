<?php

namespace Tests\Feature;

use App\Enums\NotificationType;
use App\Mail\NotificationMail;
use App\Models\ApplicationDocument;
use App\Models\ApplicationStatusHistory;
use App\Models\GuidanceAppointment;
use App\Models\GuidanceAppointmentType;
use App\Models\Notification;
use App\Models\Role;
use App\Models\Scholarship;
use App\Models\ScholarshipApplication;
use App\Models\ServiceType;
use App\Models\StudentProfile;
use App\Models\StudentServiceRequest;
use App\Models\User;
use App\Services\GuidanceAppointmentWorkflowService;
use App\Services\NotificationService;
use App\Services\ScholarshipApplicationWorkflowService;
use App\Services\StudentServiceRequestWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $studentA;

    protected User $studentB;

    protected User $counselor;

    protected StudentProfile $profileA;

    protected StudentProfile $profileB;

    protected Scholarship $scholarship;

    protected ServiceType $serviceType;

    protected GuidanceAppointmentType $appointmentType;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => Role::ADMIN], ['label' => 'Administrator']);
        $studentRole = Role::firstOrCreate(['name' => Role::STUDENT], ['label' => 'Student']);
        $counselorRole = Role::firstOrCreate(['name' => Role::COUNSELOR], ['label' => 'Counselor']);

        $this->admin = User::factory()->create(['role_id' => $adminRole->id, 'is_active' => true]);
        $this->studentA = User::factory()->create(['role_id' => $studentRole->id, 'is_active' => true]);
        $this->studentB = User::factory()->create(['role_id' => $studentRole->id, 'is_active' => true]);
        $this->counselor = User::factory()->create(['role_id' => $counselorRole->id, 'is_active' => true]);

        $this->profileA = StudentProfile::factory()->create(['user_id' => $this->studentA->id]);
        $this->profileB = StudentProfile::factory()->create(['user_id' => $this->studentB->id]);

        $this->scholarship = Scholarship::create([
            'title' => 'Notification Test Scholarship',
            'description' => 'Used by the email notification test.',
            'provider' => 'ISU OSAS',
            'scholarship_type' => 'Academic',
            'status' => Scholarship::STATUS_OPEN,
            'created_by' => $this->admin->id,
            'application_start_date' => now()->subDay()->toDateString(),
            'application_deadline' => now()->addDays(30)->toDateString(),
        ]);

        $this->serviceType = ServiceType::create([
            'name' => 'Good Moral Request',
            'description' => 'Notification test service',
            'is_active' => true,
            'display_order' => 1,
        ]);

        $this->appointmentType = GuidanceAppointmentType::firstOrCreate(
            ['name' => 'Academic Consultation'],
            ['duration_minutes' => 30, 'is_active' => true, 'display_order' => 1]
        );
    }

    // ── Helpers ─────────────────────────────────────────────

    protected function makeApplication(User $student, StudentProfile $profile, string $status = 'submitted'): ScholarshipApplication
    {
        return ScholarshipApplication::create([
            'application_number' => 'APP-'.strtoupper(substr(md5((string) $profile->id), 0, 8)),
            'student_profile_id' => $profile->id,
            'scholarship_id' => $this->scholarship->id,
            'status' => $status,
            'submitted_at' => $status === 'draft' ? null : now(),
        ]);
    }

    protected function makeServiceRequest(string $status = StudentServiceRequest::STATUS_SUBMITTED): StudentServiceRequest
    {
        return StudentServiceRequest::create([
            'request_number' => 'SR-TEST-'.random_int(1000, 9999),
            'student_profile_id' => $this->profileA->id,
            'service_type_id' => $this->serviceType->id,
            'description' => 'Notification test request.',
            'status' => $status,
            'submitted_at' => now(),
        ]);
    }

    protected function makeAppointment(string $status = GuidanceAppointment::STATUS_REQUESTED): GuidanceAppointment
    {
        $start = now()->addHours(2);

        return GuidanceAppointment::create([
            'student_profile_id' => $this->profileA->id,
            'counselor_id' => $this->counselor->id,
            'appointment_type_id' => $this->appointmentType->id,
            'appointment_type' => $this->appointmentType->name,
            'appointment_date' => $start->toDateString(),
            'start_time' => $start->format('H:i:s'),
            'end_time' => $start->copy()->addMinutes(30)->format('H:i:s'),
            'status' => $status,
        ]);
    }

    // ── A. Scholarship emails ───────────────────────────────

    public function test_application_submission_emails_the_student(): void
    {
        $application = $this->makeApplication($this->studentA, $this->profileA, 'draft');

        Mail::fake();

        $this->actingAs($this->studentA)
            ->post(route('student.applications.submit', $application))
            ->assertRedirect();

        $this->assertEquals('submitted', $application->fresh()->getRawOriginal('status'));

        Mail::assertSent(NotificationMail::class, function (NotificationMail $mail) use ($application) {
            return $mail->hasTo($this->studentA->email)
                && $mail->reference === $application->application_number
                && str_contains($mail->subjectLine, 'Application Submitted');
        });

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->studentA->id,
            'type' => 'scholarship.application_submitted',
        ]);

        $this->assertDatabaseMissing('notifications', [
            'user_id' => $this->studentB->id,
        ]);
    }

    public function test_scholarship_status_change_emails_the_student(): void
    {
        $application = $this->makeApplication($this->studentA, $this->profileA);

        Mail::fake();

        app(ScholarshipApplicationWorkflowService::class)
            ->transition($application, 'under_review', $this->admin);

        Mail::assertSent(NotificationMail::class, function (NotificationMail $mail) use ($application) {
            return $mail->hasTo($this->studentA->email)
                && $mail->reference === $application->application_number
                && $mail->status === 'Under Review';
        });

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->studentA->id,
            'type' => 'scholarship.application_under_review',
            'related_type' => 'ScholarshipApplication',
            'related_id' => $application->id,
        ]);
    }

    public function test_rejection_email_includes_authorized_staff_reason(): void
    {
        $application = $this->makeApplication($this->studentA, $this->profileA);

        Mail::fake();

        app(ScholarshipApplicationWorkflowService::class)
            ->transition($application, 'under_review', $this->admin);
        app(ScholarshipApplicationWorkflowService::class)
            ->transition($application, 'rejected', $this->admin, 'GWA does not meet the requirement.');

        Mail::assertSent(NotificationMail::class, function (NotificationMail $mail) {
            return $mail->hasTo($this->studentA->email)
                && $mail->reason === 'GWA does not meet the requirement.'
                && str_contains($mail->render(), 'GWA does not meet the requirement.');
        });
    }

    public function test_document_rejection_emails_the_student_with_remarks(): void
    {
        $application = $this->makeApplication($this->studentA, $this->profileA, 'under_review');

        $document = ApplicationDocument::create([
            'scholarship_application_id' => $application->id,
            'file_path' => 'scholarship-applications/x/cor.pdf',
            'original_filename' => 'cor.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1000,
            'verification_status' => ApplicationDocument::VERIFICATION_PENDING,
        ]);

        Mail::fake();

        $this->actingAs($this->admin)
            ->patch(route('admin.application-documents.update-verification', $document), [
                'verification_status' => 'rejected',
                'verification_remarks' => 'The scan is unreadable.',
            ])
            ->assertRedirect();

        Mail::assertSent(NotificationMail::class, function (NotificationMail $mail) {
            return $mail->hasTo($this->studentA->email)
                && $mail->reason === 'The scan is unreadable.'
                && str_contains($mail->subjectLine, 'Document Rejected');
        });

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->studentA->id,
            'type' => 'scholarship.document_rejected',
        ]);
    }

    // ── B. Student service email notifications ──────────────

    public function test_service_request_submission_emails_the_student(): void
    {
        Mail::fake();

        $this->actingAs($this->studentA)->post(route('student.services.store'), [
            'service_type_id' => $this->serviceType->id,
            'description' => 'Please process my request.',
        ]);

        $request = StudentServiceRequest::where('student_profile_id', $this->profileA->id)->firstOrFail();

        Mail::assertSent(NotificationMail::class, function (NotificationMail $mail) use ($request) {
            return $mail->hasTo($this->studentA->email)
                && $mail->reference === $request->request_number
                && str_contains($mail->subjectLine, 'Service Request Submitted');
        });

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->studentA->id,
            'type' => 'student_services.request_submitted',
        ]);
    }

    public function test_service_request_status_change_emails_the_student(): void
    {
        $request = $this->makeServiceRequest();

        Mail::fake();

        app(StudentServiceRequestWorkflowService::class)
            ->transition($request, StudentServiceRequest::STATUS_PROCESSING, $this->admin);

        Mail::assertSent(NotificationMail::class, function (NotificationMail $mail) use ($request) {
            return $mail->hasTo($this->studentA->email)
                && $mail->reference === $request->request_number
                && $mail->status === 'Processing';
        });

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->studentA->id,
            'type' => 'student_services.request_processing',
        ]);
    }

    public function test_first_assignment_emails_the_student(): void
    {
        $request = $this->makeServiceRequest();

        Mail::fake();

        $this->actingAs($this->admin)
            ->patch(route('admin.student-service-requests.assign', $request), [
                'handled_by' => $this->counselor->id,
            ])
            ->assertRedirect();

        Mail::assertSent(NotificationMail::class, function (NotificationMail $mail) use ($request) {
            return $mail->hasTo($this->studentA->email)
                && $mail->reference === $request->request_number;
        });

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->studentA->id,
            'type' => 'student_services.request_processing',
        ]);
    }

    // ── C. Guidance email notifications ─────────────────────

    public function test_guidance_confirmation_emails_the_student(): void
    {
        $appointment = $this->makeAppointment();

        Mail::fake();

        app(GuidanceAppointmentWorkflowService::class)
            ->transition($appointment, GuidanceAppointment::STATUS_CONFIRMED, $this->counselor);

        Mail::assertSent(NotificationMail::class, function (NotificationMail $mail) {
            return $mail->hasTo($this->studentA->email)
                && str_contains($mail->subjectLine, 'Appointment Confirmed');
        });

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->studentA->id,
            'type' => 'guidance.appointment_confirmed',
        ]);
    }

    public function test_guidance_cancellation_by_student_emails_the_counselor(): void
    {
        $appointment = $this->makeAppointment(GuidanceAppointment::STATUS_CONFIRMED);

        // Move the appointment far enough ahead to satisfy the cancellation notice rule.
        $future = now()->addDays(3);
        $appointment->update([
            'appointment_date' => $future->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '09:30:00',
        ]);

        Mail::fake();

        $this->actingAs($this->studentA)
            ->patch(route('student.guidance.appointments.cancel', $appointment->fresh()))
            ->assertRedirect();

        Mail::assertSent(NotificationMail::class, function (NotificationMail $mail) {
            return $mail->hasTo($this->counselor->email)
                && str_contains($mail->subjectLine, 'Appointment Cancelled');
        });

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->counselor->id,
            'type' => 'guidance.appointment_cancelled',
        ]);
    }

    public function test_guidance_email_never_includes_confidential_counselor_notes(): void
    {
        $appointment = $this->makeAppointment();
        $appointment->update(['counselor_notes' => 'CONFIDENTIAL-CASE-DETAILS']);

        Mail::fake();

        app(GuidanceAppointmentWorkflowService::class)
            ->transition($appointment, GuidanceAppointment::STATUS_CONFIRMED, $this->counselor);

        Mail::assertSent(NotificationMail::class, function (NotificationMail $mail) {
            return ! str_contains($mail->render(), 'CONFIDENTIAL-CASE-DETAILS');
        });

        $this->assertDatabaseMissing('notifications', [
            'user_id' => $this->studentA->id,
            'type' => 'guidance.appointment_confirmed',
            'message' => 'CONFIDENTIAL-CASE-DETAILS',
        ]);
    }

    public function test_appointment_reminder_command_emails_once(): void
    {
        $appointment = $this->makeAppointment(GuidanceAppointment::STATUS_CONFIRMED);

        Mail::fake();

        $this->artisan('guidance:send-reminders')->assertExitCode(0);

        $this->assertNotNull($appointment->fresh()->reminder_sent_at);

        Mail::assertSent(NotificationMail::class, function (NotificationMail $mail) {
            return $mail->hasTo($this->studentA->email)
                && str_contains($mail->subjectLine, 'Appointment Reminder');
        });

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->studentA->id,
            'type' => 'guidance.appointment_reminder',
        ]);

        // A second run must not send a duplicate reminder.
        Mail::fake();
        $this->artisan('guidance:send-reminders')->assertExitCode(0);
        Mail::assertNothingSent();
    }

    // ── D. Ownership / isolation ────────────────────────────

    public function test_notification_only_emails_the_correct_student(): void
    {
        $applicationA = $this->makeApplication($this->studentA, $this->profileA);

        Mail::fake();

        app(ScholarshipApplicationWorkflowService::class)
            ->transition($applicationA, 'under_review', $this->admin);

        Mail::assertSent(NotificationMail::class, fn (NotificationMail $mail) => $mail->hasTo($this->studentA->email));
        Mail::assertNotSent(NotificationMail::class, fn (NotificationMail $mail) => $mail->hasTo($this->studentB->email));
    }

    public function test_student_cannot_mark_another_students_notification_read(): void
    {
        $notification = Notification::create([
            'user_id' => $this->studentA->id,
            'type' => 'scholarship.application_approved',
            'title' => 'Approved',
            'message' => 'Your application was approved.',
        ]);

        $this->actingAs($this->studentB)
            ->patch(route('notifications.read', $notification))
            ->assertForbidden();

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_notification_center_shows_reference_status_and_unread_badge(): void
    {
        app(NotificationService::class)->notifyUser(
            $this->studentA,
            NotificationType::SCHOLARSHIP_APPLICATION_APPROVED,
            'Application Approved',
            'Your application was approved.',
            'ScholarshipApplication',
            $this->makeApplication($this->studentA, $this->profileA)->id,
            [
                'reference' => 'APP-SHOWCASE-1',
                'reference_label' => 'Application No.',
                'status' => 'Approved',
            ],
            sendEmail: false,
        );

        $response = $this->actingAs($this->studentA)->get(route('notifications.index'));

        $response->assertOk();
        $response->assertSee('APP-SHOWCASE-1');
        $response->assertSee('Approved');
        $response->assertSee('1 unread');
    }

    // ── E. Duplicate prevention ─────────────────────────────

    public function test_duplicate_event_sends_only_one_email(): void
    {
        Mail::fake();

        $service = app(NotificationService::class);

        $args = [
            $this->studentA,
            NotificationType::STUDENT_SERVICE_REQUEST_COMPLETED,
            'Service Request Completed',
            'Your request was completed.',
            'StudentServiceRequest',
            4242,
            ['reference' => 'SR-DUP-1', 'status' => 'Completed'],
            true,
        ];

        $first = $service->notifyUser(...$args);
        $second = $service->notifyUser(...$args);

        $this->assertNotNull($first);
        $this->assertNull($second);

        $this->assertEquals(1, Notification::where('user_id', $this->studentA->id)->count());
        Mail::assertSent(NotificationMail::class, 1);
    }

    // ── F. Rollback safety ──────────────────────────────────

    public function test_rolled_back_change_creates_no_notification_or_email(): void
    {
        $application = $this->makeApplication($this->studentA, $this->profileA);

        ApplicationStatusHistory::creating(function () {
            throw new \RuntimeException('forced rollback');
        });

        Mail::fake();

        try {
            app(ScholarshipApplicationWorkflowService::class)
                ->transition($application, 'under_review', $this->admin);
        } catch (\Throwable) {
            // Expected: the transaction rolled back.
        }

        $this->assertEquals('submitted', $application->fresh()->getRawOriginal('status'));
        $this->assertDatabaseCount('notifications', 0);
        Mail::assertNothingSent();
    }

    // ── G. Failure handling / missing email ─────────────────

    public function test_smtp_failure_preserves_business_data_and_marks_not_emailed(): void
    {
        $application = $this->makeApplication($this->studentA, $this->profileA);

        Mail::shouldReceive('to')
            ->once()
            ->andThrow(new \RuntimeException('SMTP unavailable'));

        // Must NOT throw: email delivery is best-effort and never loses the update.
        app(ScholarshipApplicationWorkflowService::class)
            ->transition($application, 'under_review', $this->admin);

        $this->assertEquals('under_review', $application->fresh()->getRawOriginal('status'));
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->studentA->id,
            'type' => 'scholarship.application_under_review',
        ]);
        $this->assertNull(Notification::where('user_id', $this->studentA->id)->first()->emailed_at);
    }

    public function test_recipient_without_email_still_gets_in_app_notification(): void
    {
        $this->studentA->update(['email' => '']);

        $application = $this->makeApplication($this->studentA, $this->profileA);

        Mail::fake();

        app(ScholarshipApplicationWorkflowService::class)
            ->transition($application, 'under_review', $this->admin);

        Mail::assertNothingSent();
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->studentA->id,
            'type' => 'scholarship.application_under_review',
        ]);
    }
}
