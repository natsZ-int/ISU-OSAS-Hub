<?php

namespace Tests\Feature;

use App\Mail\NotificationMail;
use App\Models\Notification;
use App\Models\Role;
use App\Models\Scholarship;
use App\Models\StudentEnrollmentVerification;
use App\Models\StudentProfile;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ScholarshipAnnouncementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $eligibleStudent;

    protected User $borderlineStudent;

    protected User $ineligibleStudent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesSeeder::class);
        Mail::fake();

        $adminRole = Role::where('name', Role::ADMIN)->firstOrFail();
        $studentRole = Role::where('name', Role::STUDENT)->firstOrFail();

        $this->admin = User::factory()->create(['role_id' => $adminRole->id, 'is_active' => true]);

        $this->eligibleStudent = $this->studentWithProfile($studentRole, [
            'student_number' => '2026-OLD-0001',
            'gwa' => 1.50,
            'family_income' => 100000,
            'program' => 'BSCS',
            'year_level' => 2,
        ]);

        // Qualifies once the GWA ceiling is relaxed to 2.75.
        $this->borderlineStudent = $this->studentWithProfile($studentRole, [
            'student_number' => '2026-OLD-0002',
            'gwa' => 2.50,
            'family_income' => 120000,
            'program' => 'BSCS',
            'year_level' => 3,
        ]);

        $this->ineligibleStudent = $this->studentWithProfile($studentRole, [
            'student_number' => '2026-OLD-0003',
            'gwa' => 1.25,
            'family_income' => 900000,
            'program' => 'BSCS',
            'year_level' => 2,
        ]);
    }

    private function studentWithProfile(Role $role, array $overrides = []): User
    {
        $student = User::factory()->create(['role_id' => $role->id, 'is_active' => true]);

        StudentProfile::factory()->create(array_merge([
            'user_id' => $student->id,
            'enrollment_status' => 'enrolled',
        ], $overrides));

        return $student;
    }

    private function scholarshipPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Eligibility Notice Scholarship',
            'description' => 'Awarded to qualified students.',
            'provider' => 'ISU OSAS',
            'scholarship_type' => 'Academic',
            'minimum_gwa' => 2.00,
            'maximum_family_income' => 200000,
            'eligible_programs_text' => 'BSCS',
            'eligible_year_levels_text' => '2, 3',
            'required_enrollment_status' => 'enrolled',
            'application_start_date' => now()->subDay()->toDateString(),
            'application_deadline' => now()->addDays(30)->toDateString(),
            'status' => Scholarship::STATUS_OPEN,
        ], $overrides);
    }

    private function makeScholarship(array $overrides = []): Scholarship
    {
        return Scholarship::create(array_merge([
            'title' => 'Eligibility Notice Scholarship',
            'description' => 'Awarded to qualified students.',
            'provider' => 'ISU OSAS',
            'scholarship_type' => 'Academic',
            'minimum_gwa' => 2.00,
            'maximum_family_income' => 200000,
            'eligible_programs' => ['BSCS'],
            'eligible_year_levels' => [2, 3],
            'required_enrollment_status' => 'enrolled',
            'application_start_date' => now()->subDay()->toDateString(),
            'application_deadline' => now()->addDays(30)->toDateString(),
            'status' => Scholarship::STATUS_OPEN,
            'created_by' => $this->admin->id,
        ], $overrides));
    }

    private function notificationCount(User $user, Scholarship $scholarship): int
    {
        return Notification::query()
            ->where('user_id', $user->id)
            ->where('type', 'scholarship.announcement')
            ->where('related_type', 'Scholarship')
            ->where('related_id', $scholarship->id)
            ->count();
    }

    public function test_creating_an_open_scholarship_notifies_and_emails_only_eligible_students(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.scholarships.store'), $this->scholarshipPayload())
            ->assertRedirect(route('admin.scholarships.index'))
            ->assertSessionHas('status');

        $scholarship = Scholarship::firstOrFail();

        $notification = Notification::where('user_id', $this->eligibleStudent->id)->first();
        $this->assertNotNull($notification);
        $this->assertSame('scholarship.announcement', $notification->type);
        $this->assertSame('Scholarship', $notification->related_type);
        $this->assertSame($scholarship->id, $notification->related_id);
        $this->assertStringContainsString('eligible', $notification->message);
        $this->assertNotNull($notification->emailed_at, 'The announcement email should have been sent.');

        $this->assertSame(0, $this->notificationCount($this->borderlineStudent, $scholarship));
        $this->assertSame(0, $this->notificationCount($this->ineligibleStudent, $scholarship));

        Mail::assertSent(NotificationMail::class, 1);
        Mail::assertSent(NotificationMail::class, fn (NotificationMail $mail) => $mail->hasTo($this->eligibleStudent->email));
        Mail::assertNotSent(NotificationMail::class, fn (NotificationMail $mail) => $mail->hasTo($this->ineligibleStudent->email));
    }

    public function test_creating_a_draft_scholarship_does_not_notify_students(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.scholarships.store'), $this->scholarshipPayload([
                'status' => Scholarship::STATUS_DRAFT,
            ]))
            ->assertRedirect(route('admin.scholarships.index'));

        $this->assertSame(0, Notification::where('type', 'scholarship.announcement')->count());
        Mail::assertNothingSent();
    }

    public function test_editing_a_draft_scholarship_to_open_notifies_eligible_students(): void
    {
        $scholarship = $this->makeScholarship(['status' => Scholarship::STATUS_DRAFT]);

        $this->actingAs($this->admin)
            ->put(route('admin.scholarships.update', $scholarship), $this->scholarshipPayload([
                'status' => Scholarship::STATUS_OPEN,
            ]))
            ->assertRedirect(route('admin.scholarships.edit', $scholarship));

        $this->assertSame(1, $this->notificationCount($this->eligibleStudent, $scholarship));
        $this->assertSame(0, $this->notificationCount($this->ineligibleStudent, $scholarship));
        Mail::assertSent(NotificationMail::class, 1);
    }

    public function test_broadening_criteria_notifies_only_newly_eligible_students(): void
    {
        $scholarship = $this->makeScholarship();

        // First announcement reaches only the currently eligible student.
        $this->actingAs($this->admin)
            ->patch(route('admin.scholarships.announce', $scholarship))
            ->assertSessionHas('status');

        $this->assertSame(1, $this->notificationCount($this->eligibleStudent, $scholarship));
        $this->assertSame(0, $this->notificationCount($this->borderlineStudent, $scholarship));

        // Reading the notification must not make the student eligible for a
        // second copy of the same announcement.
        Notification::where('user_id', $this->eligibleStudent->id)->update(['read_at' => now()]);

        $this->actingAs($this->admin)
            ->put(route('admin.scholarships.update', $scholarship), $this->scholarshipPayload([
                'minimum_gwa' => 2.75,
            ]))
            ->assertRedirect(route('admin.scholarships.edit', $scholarship));

        $this->assertSame(1, $this->notificationCount($this->eligibleStudent, $scholarship));
        $this->assertSame(1, $this->notificationCount($this->borderlineStudent, $scholarship));
        $this->assertSame(0, $this->notificationCount($this->ineligibleStudent, $scholarship));
    }

    public function test_manual_announcement_does_not_duplicate_unread_notifications(): void
    {
        $scholarship = $this->makeScholarship();

        $this->actingAs($this->admin)
            ->patch(route('admin.scholarships.announce', $scholarship))
            ->assertSessionHas('status');

        $this->actingAs($this->admin)
            ->patch(route('admin.scholarships.announce', $scholarship))
            ->assertSessionHas('status');

        $this->assertSame(1, $this->notificationCount($this->eligibleStudent, $scholarship));
        Mail::assertSent(NotificationMail::class, 1);
    }

    public function test_non_open_scholarship_cannot_be_announced(): void
    {
        $scholarship = $this->makeScholarship(['status' => Scholarship::STATUS_CLOSED]);

        $this->actingAs($this->admin)
            ->patch(route('admin.scholarships.announce', $scholarship))
            ->assertSessionHas('error');

        $this->assertSame(0, Notification::where('type', 'scholarship.announcement')->count());
        Mail::assertNothingSent();
    }

    public function test_admin_list_exposes_the_notify_action_for_open_scholarships(): void
    {
        $scholarship = $this->makeScholarship();

        $this->actingAs($this->admin)
            ->get(route('admin.scholarships.index'))
            ->assertOk()
            ->assertSee('Notify Eligible')
            ->assertSee(route('admin.scholarships.announce', $scholarship), false);
    }

    public function test_student_is_auto_notified_when_their_profile_update_makes_them_eligible(): void
    {
        $scholarship = $this->makeScholarship();
        $profile = $this->borderlineStudent->studentProfile;

        $this->actingAs($this->borderlineStudent)
            ->put('/student/profile', [
                'first_name' => $profile->first_name,
                'last_name' => $profile->last_name,
                'gwa' => 1.90,
                'family_income' => 120000,
            ])
            ->assertRedirect(route('student.profile.show'));

        $this->assertSame(1, $this->notificationCount($this->borderlineStudent, $scholarship));
        Mail::assertSent(NotificationMail::class, fn (NotificationMail $mail) => $mail->hasTo($this->borderlineStudent->email));
        $this->assertSame(0, $this->notificationCount($this->eligibleStudent, $scholarship));
    }

    public function test_staff_profile_fix_auto_notifies_the_newly_eligible_student(): void
    {
        $scholarship = $this->makeScholarship();
        $profile = $this->borderlineStudent->studentProfile;

        $this->actingAs($this->admin)
            ->put(route('admin.student-profiles.update', $profile), [
                'student_number' => $profile->student_number,
                'first_name' => $profile->first_name,
                'last_name' => $profile->last_name,
                'middle_name' => $profile->middle_name,
                'contact_number' => $profile->contact_number,
                'college' => $profile->college,
                'program' => 'BSCS',
                'year_level' => 3,
                'enrollment_status' => 'enrolled',
                'gwa' => 1.90,
                'family_income' => 120000,
                'address' => $profile->address,
            ])
            ->assertRedirect(route('admin.student-profiles.show', $profile));

        $this->assertSame(1, $this->notificationCount($this->borderlineStudent, $scholarship));
    }

    public function test_verifying_enrollment_auto_notifies_the_newly_eligible_student(): void
    {
        $scholarship = $this->makeScholarship();

        $profile = $this->borderlineStudent->studentProfile;
        $profile->update(['enrollment_status' => 'unknown', 'gwa' => 1.90]);

        $verification = StudentEnrollmentVerification::create([
            'student_profile_id' => $profile->id,
            'academic_year' => config('academic.current_year'),
            'semester' => config('academic.current_semester'),
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin)
            ->patch(route('enrollment-verifications.update', $verification), [
                'status' => 'verified',
                'academic_year' => config('academic.current_year'),
                'semester' => config('academic.current_semester'),
            ])
            ->assertRedirect(route('enrollment-verifications.index'));

        $this->assertSame(1, $this->notificationCount($this->borderlineStudent, $scholarship));
    }

    public function test_students_cannot_trigger_announcements(): void
    {
        $scholarship = $this->makeScholarship();

        $this->actingAs($this->eligibleStudent)
            ->patch(route('admin.scholarships.announce', $scholarship))
            ->assertStatus(403);

        $this->assertSame(0, Notification::where('type', 'scholarship.announcement')->count());
        Mail::assertNothingSent();
    }
}
