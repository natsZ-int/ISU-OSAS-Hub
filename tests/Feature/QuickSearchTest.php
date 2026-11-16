<?php

namespace Tests\Feature;

use App\Models\AuditLog;
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
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuickSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    // ── Helpers ───────────────────────────────────────────────

    private function makeUser(string $role, array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role_id' => Role::where('name', $role)->firstOrFail()->id,
            'is_active' => true,
        ], $attributes));
    }

    private function makeStudent(string $firstName = 'Maria', string $lastName = 'Santos', ?string $number = null): array
    {
        $user = $this->makeUser('student', [
            'name' => $firstName.' '.$lastName,
        ]);

        $profile = StudentProfile::factory()->create([
            'user_id' => $user->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'student_number' => $number ?? 'ISU-'.fake()->unique()->numerify('####-#####'),
        ]);

        return [$user, $profile];
    }

    private function makeScholarship(string $title, array $attributes = []): Scholarship
    {
        return Scholarship::create(array_merge([
            'title' => $title,
            'description' => 'Financial assistance for deserving students.',
            'provider' => 'ISU',
            'scholarship_type' => 'Merit-Based',
            'application_start_date' => now()->subDay(),
            'application_deadline' => now()->addMonth(),
            'status' => 'open',
        ], $attributes));
    }

    private function makeApplication(StudentProfile $profile, Scholarship $scholarship, string $number): ScholarshipApplication
    {
        return ScholarshipApplication::create([
            'application_number' => $number,
            'student_profile_id' => $profile->id,
            'scholarship_id' => $scholarship->id,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
    }

    private function makeAppointment(StudentProfile $profile, User $counselor, string $reason = 'Course planning'): GuidanceAppointment
    {
        $typeId = GuidanceAppointmentType::firstOrCreate(
            ['name' => 'Academic Counseling'],
            [
                'duration_minutes' => 30,
                'is_active' => true,
                'display_order' => 1,
            ],
        )->id;

        return GuidanceAppointment::create([
            'student_profile_id' => $profile->id,
            'counselor_id' => $counselor->id,
            'appointment_type_id' => $typeId,
            'appointment_date' => now()->addDay(),
            'start_time' => '09:00:00',
            'end_time' => '09:30:00',
            'appointment_type' => 'Academic Counseling',
            'reason' => $reason,
            'status' => 'requested',
        ]);
    }

    private function makeServiceRequest(StudentProfile $profile, ServiceType $type, string $number, string $description = 'Please process my request.'): StudentServiceRequest
    {
        return StudentServiceRequest::create([
            'request_number' => $number,
            'student_profile_id' => $profile->id,
            'service_type_id' => $type->id,
            'description' => $description,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
    }

    // ── Admin search ──────────────────────────────────────────

    public function test_admin_quick_search_matches_students_by_partial_and_tokenized_name(): void
    {
        $admin = $this->makeUser('admin');
        $suffix = substr(uniqid(), -6);
        $firstName = 'Marian'.$suffix;
        $lastName = 'Santos'.$suffix;
        [, $profile] = $this->makeStudent($firstName, $lastName);
        $this->makeStudent('Pedro', 'Reyes');

        $this->actingAs($admin)
            ->getJson(route('admin.quick-search', ['module' => 'students', 'q' => 'santos'.$suffix]))
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonFragment(['title' => $firstName.' '.$lastName]);

        $this->actingAs($admin)
            ->getJson(route('admin.quick-search', ['module' => 'students', 'q' => 'marian'.$suffix.' santos'.$suffix]))
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonFragment(['id' => $profile->id]);
    }

    public function test_admin_quick_search_handles_each_admin_module(): void
    {
        $admin = $this->makeUser('admin');
        [$student, $profile] = $this->makeStudent('Ana', 'Cruz');
        $scholarship = $this->makeScholarship('Presidential Scholar Grant');
        $application = $this->makeApplication($profile, $scholarship, 'APP-2026-0042');
        $serviceType = ServiceType::create([
            'name' => 'Transcript of Records',
            'description' => 'Official transcript request',
            'is_active' => true,
        ]);
        $serviceRequest = $this->makeServiceRequest($profile, $serviceType, 'REQ-2026-0007', 'Needed for transfer.');
        $appointment = $this->makeAppointment($profile, $this->makeUser('counselor', ['name' => 'Carla Diaz']));
        $log = AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'Scholarship Updated',
            'module' => 'Scholarship Management',
            'record_type' => 'Scholarship',
            'record_id' => $scholarship->id,
            'description' => 'Updated the Presidential Scholar Grant record.',
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.quick-search', ['module' => 'scholarships', 'q' => 'presidential']))
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonFragment(['id' => $scholarship->id]);

        $this->actingAs($admin)
            ->getJson(route('admin.quick-search', ['module' => 'applications', 'q' => 'APP-2026']))
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonFragment(['id' => $application->id]);

        $this->actingAs($admin)
            ->getJson(route('admin.quick-search', ['module' => 'users', 'q' => 'ana']))
            ->assertOk()
            ->assertJsonFragment(['id' => $student->id]);

        $this->actingAs($admin)
            ->getJson(route('admin.quick-search', ['module' => 'service-requests', 'q' => 'transcript']))
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonFragment(['id' => $serviceRequest->id]);

        $this->actingAs($admin)
            ->getJson(route('admin.quick-search', ['module' => 'appointments', 'q' => 'carla']))
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonFragment(['id' => $appointment->id]);

        $this->actingAs($admin)
            ->getJson(route('admin.quick-search', ['module' => 'audit-logs', 'q' => 'presidential']))
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonFragment(['id' => $log->id]);

        $this->actingAs($admin)
            ->getJson(route('admin.quick-search', ['module' => 'analytics-services', 'q' => 'transfer']))
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonFragment(['id' => $serviceRequest->id]);
    }

    public function test_admin_quick_search_requires_admin_role(): void
    {
        $student = $this->makeStudent()[0];
        $counselor = $this->makeUser('counselor');

        $this->actingAs($student)
            ->getJson(route('admin.quick-search', ['module' => 'students', 'q' => 'maria']))
            ->assertForbidden();

        $this->actingAs($counselor)
            ->getJson(route('admin.quick-search', ['module' => 'students', 'q' => 'maria']))
            ->assertForbidden();

        $this->getJson(route('admin.quick-search', ['module' => 'students', 'q' => 'maria']))
            ->assertForbidden();
    }

    // ── Student search ────────────────────────────────────────

    public function test_student_quick_search_only_returns_own_records(): void
    {
        [$student, $profile] = $this->makeStudent('Liza', 'Mendoza');
        [, $otherProfile] = $this->makeStudent('Marco', 'Villa');

        $ownScholarship = $this->makeScholarship('University Grant');
        $otherScholarship = $this->makeScholarship('External Grant');

        $ownApplication = $this->makeApplication($profile, $ownScholarship, 'APP-OWN-001');
        $this->makeApplication($otherProfile, $otherScholarship, 'APP-OTHER-002');

        $serviceType = ServiceType::create([
            'name' => 'Good Moral Certificate',
            'description' => 'Certificate request',
            'is_active' => true,
        ]);
        $ownRequest = $this->makeServiceRequest($profile, $serviceType, 'REQ-OWN-001');
        $this->makeServiceRequest($otherProfile, $serviceType, 'REQ-OTHER-002');

        $counselor = $this->makeUser('counselor', ['name' => 'Nora Lim']);
        $ownAppointment = $this->makeAppointment($profile, $counselor);
        $this->makeAppointment($otherProfile, $counselor);

        $this->actingAs($student)
            ->getJson(route('student.quick-search', ['module' => 'applications', 'q' => 'APP']))
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonFragment(['id' => $ownApplication->id]);

        $this->actingAs($student)
            ->getJson(route('student.quick-search', ['module' => 'service-requests', 'q' => 'certificate']))
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonFragment(['id' => $ownRequest->id]);

        $this->actingAs($student)
            ->getJson(route('student.quick-search', ['module' => 'appointments', 'q' => 'nora']))
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonFragment(['id' => $ownAppointment->id]);
    }

    public function test_student_can_search_available_scholarships_services_and_counselors(): void
    {
        [$student] = $this->makeStudent();
        $scholarship = $this->makeScholarship('Science and Technology Grant');
        $serviceType = ServiceType::create([
            'name' => 'Counseling Referral',
            'description' => 'Referral to the guidance office',
            'is_active' => true,
        ]);
        $counselor = $this->makeUser('counselor', ['name' => 'Grace Tan']);

        $this->actingAs($student)
            ->getJson(route('student.quick-search', ['module' => 'scholarships', 'q' => 'science']))
            ->assertOk()
            ->assertJsonFragment(['id' => $scholarship->id]);

        $this->actingAs($student)
            ->getJson(route('student.quick-search', ['module' => 'services', 'q' => 'referral']))
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonFragment(['id' => $serviceType->id]);

        $this->actingAs($student)
            ->getJson(route('student.quick-search', ['module' => 'counselors', 'q' => 'grace']))
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonFragment(['id' => $counselor->id]);
    }

    public function test_student_quick_search_rejects_unknown_modules(): void
    {
        [$student] = $this->makeStudent();

        $this->actingAs($student)
            ->getJson(route('student.quick-search', ['module' => 'users', 'q' => 'maria']))
            ->assertOk()
            ->assertJsonPath('count', 0)
            ->assertJsonPath('results', []);
    }

    // ── Counselor search ──────────────────────────────────────

    public function test_counselor_quick_search_only_returns_own_appointments(): void
    {
        $counselor = $this->makeUser('counselor', ['name' => 'Carla Diaz']);
        $otherCounselor = $this->makeUser('counselor', ['name' => 'Other Counselor']);
        [, $profile] = $this->makeStudent('Jose', 'Rizal');
        [, $otherProfile] = $this->makeStudent('Andres', 'Bonifacio');

        $ownAppointment = $this->makeAppointment($profile, $counselor, 'Scholarship concerns');
        $this->makeAppointment($otherProfile, $otherCounselor, 'Scholarship concerns');

        $this->actingAs($counselor)
            ->getJson(route('counselor.quick-search', ['module' => 'appointments', 'q' => 'scholarship']))
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonFragment(['id' => $ownAppointment->id]);
    }

    // ── Notifications search ──────────────────────────────────

    public function test_notification_quick_search_only_returns_current_users_notifications(): void
    {
        [$student] = $this->makeStudent();
        [$other] = $this->makeStudent('Other', 'Student');

        $own = Notification::create([
            'user_id' => $student->id,
            'type' => 'scholarship.application_approved',
            'title' => 'Scholarship Approved',
            'message' => 'Your scholarship application was approved.',
            'related_type' => 'ScholarshipApplication',
            'related_id' => 1,
        ]);

        Notification::create([
            'user_id' => $other->id,
            'type' => 'scholarship.application_approved',
            'title' => 'Scholarship Approved',
            'message' => 'Your scholarship application was approved.',
            'related_type' => 'ScholarshipApplication',
            'related_id' => 2,
        ]);

        $this->actingAs($student)
            ->getJson(route('notifications.quick-search', ['q' => 'approved']))
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonFragment(['id' => $own->id]);
    }

    // ── Validation and safety ─────────────────────────────────

    public function test_quick_search_validates_term_and_limit(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)
            ->getJson(route('admin.quick-search', ['module' => 'students', 'q' => str_repeat('a', 201)]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('q');

        $this->actingAs($admin)
            ->getJson(route('admin.quick-search', ['module' => 'students', 'q' => 'maria', 'limit' => 21]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('limit');

        $this->actingAs($admin)
            ->getJson(route('admin.quick-search', ['module' => 'students', 'q' => 'maria', 'limit' => 0]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('limit');
    }

    public function test_empty_term_returns_an_empty_result_set(): void
    {
        $admin = $this->makeUser('admin');
        $this->makeStudent('Maria', 'Santos');

        $this->actingAs($admin)
            ->getJson(route('admin.quick-search', ['module' => 'students', 'q' => '   ']))
            ->assertOk()
            ->assertJsonPath('count', 0)
            ->assertJsonPath('results', []);
    }

    public function test_sql_wildcards_are_treated_as_literal_text(): void
    {
        $admin = $this->makeUser('admin');
        $this->makeStudent('Maria', 'Santos');

        $this->actingAs($admin)
            ->getJson(route('admin.quick-search', ['module' => 'students', 'q' => '%']))
            ->assertOk()
            ->assertJsonPath('count', 0);
    }

    // ── Page wiring ───────────────────────────────────────────

    public function test_searchable_pages_expose_live_search_endpoints(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertOk()
            ->assertSee('data-live-search', false)
            ->assertSee(route('admin.quick-search', ['module' => 'users']), false);

        $this->actingAs($admin)
            ->get('/admin/guidance/appointments')
            ->assertOk()
            ->assertSee(route('admin.quick-search', ['module' => 'appointments']), false);

        $this->actingAs($admin)
            ->get('/admin/analytics')
            ->assertOk()
            ->assertSee(route('admin.quick-search', ['module' => 'analytics']), false);

        [$student] = $this->makeStudent();
        $counselor = $this->makeUser('counselor');

        $this->actingAs($student)
            ->get('/student/applications')
            ->assertOk()
            ->assertSee(route('student.quick-search', ['module' => 'applications']), false);

        $this->actingAs($student)
            ->get('/student/services')
            ->assertOk()
            ->assertSee(route('student.quick-search', ['module' => 'services']), false);

        $this->actingAs($student)
            ->get('/notifications')
            ->assertOk()
            ->assertSee(route('notifications.quick-search'), false);

        $this->actingAs($counselor)
            ->get('/counselor/guidance/appointments')
            ->assertOk()
            ->assertSee(route('counselor.quick-search', ['module' => 'appointments']), false);
    }
}
