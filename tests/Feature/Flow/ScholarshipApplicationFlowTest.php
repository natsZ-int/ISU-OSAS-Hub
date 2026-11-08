<?php

namespace Tests\Feature\Flow;

use App\Models\ApplicationDocument;
use App\Models\Role;
use App\Models\Scholarship;
use App\Models\ScholarshipApplication;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ScholarshipApplicationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $studentA;

    protected User $studentB;

    protected User $counselor;

    protected StudentProfile $profileA;

    protected StudentProfile $profileB;

    protected Scholarship $scholarship;

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
            'title' => 'Integration Flow Scholarship',
            'description' => 'Used by the application flow integration test.',
            'provider' => 'ISU OSAS',
            'scholarship_type' => 'Academic',
            'status' => Scholarship::STATUS_OPEN,
            'created_by' => $this->admin->id,
            'application_start_date' => now()->subDay()->toDateString(),
            'application_deadline' => now()->addDays(30)->toDateString(),
        ]);

        $this->scholarship->requirements()->create([
            'requirement_name' => 'Certificate of Registration',
            'description' => 'Scanned COR',
            'is_required' => true,
            'display_order' => 1,
        ]);
    }

    public function test_scholarship_detail_page_renders_without_error(): void
    {
        // Regression: the detail view previously used an undefined variable and 500'd.
        $response = $this->actingAs($this->studentA)
            ->get(route('student.scholarships.show', $this->scholarship));

        $response->assertOk();
        $response->assertSee('Start Application');
        $response->assertDontSee('existingApplication', false);
    }

    public function test_application_show_page_renders_status_badge_for_enum_status(): void
    {
        // Regression: the shared status-badge component cast a native backed
        // enum to string directly, causing a 500 on the application page.
        $this->actingAs($this->studentA)
            ->post(route('student.applications.store', $this->scholarship));

        $application = ScholarshipApplication::where('student_profile_id', $this->profileA->id)->firstOrFail();
        $application->update(['status' => 'submitted']);

        $response = $this->actingAs($this->studentA)
            ->get(route('student.applications.show', $application));

        $response->assertOk();
        $response->assertSee($application->application_number);
        $response->assertSee('Submitted');
    }

    public function test_full_application_flow_creates_history_audit_and_notifications(): void
    {
        Storage::fake('local');

        // 1. Student creates draft
        $this->actingAs($this->studentA)
            ->post(route('student.applications.store', $this->scholarship))
            ->assertRedirect();

        $application = ScholarshipApplication::where('student_profile_id', $this->profileA->id)->first();
        $this->assertNotNull($application);
        $this->assertEquals('draft', $application->getRawOriginal('status'));
        $this->assertDatabaseHas('application_status_histories', [
            'scholarship_application_id' => $application->id,
            'new_status' => 'draft',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'Application Started',
            'record_type' => 'ScholarshipApplication',
            'record_id' => $application->id,
        ]);

        // 2. Student uploads the required document
        $file = UploadedFile::fake()->create('cor.pdf', 100, 'application/pdf');
        $this->actingAs($this->studentA)
            ->post(route('student.documents.store', $application), [
                'scholarship_requirement_id' => $this->scholarship->requirements->first()->id,
                'document' => $file,
            ])
            ->assertRedirect();

        $document = ApplicationDocument::where('scholarship_application_id', $application->id)->first();
        $this->assertNotNull($document);
        $this->assertEquals('pending', $document->verification_status);

        // Audit must reference the DOCUMENT id (not the application id).
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'Document Uploaded',
            'record_type' => 'ApplicationDocument',
            'record_id' => $document->id,
        ]);

        // 3. Student submits
        $this->actingAs($this->studentA)
            ->post(route('student.applications.submit', $application))
            ->assertRedirect();

        $this->assertEquals('submitted', $application->fresh()->getRawOriginal('status'));
        $this->assertDatabaseHas('application_status_histories', [
            'scholarship_application_id' => $application->id,
            'new_status' => 'submitted',
        ]);

        // 4. Admin moves to under review — student is notified
        $this->actingAs($this->admin)
            ->patch(route('admin.scholarship-applications.transition', $application), [
                'status' => 'under_review',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->studentA->id,
            'type' => 'scholarship.application_under_review',
            'related_type' => 'ScholarshipApplication',
            'related_id' => $application->id,
        ]);

        // 5. Admin verifies the document
        $this->actingAs($this->admin)
            ->patch(route('admin.application-documents.update-verification', $document), [
                'verification_status' => 'verified',
            ])
            ->assertRedirect();

        $this->assertEquals('verified', $document->fresh()->verification_status);

        // 5b. Move through document_verification, then approve (existing FSM path)
        $this->actingAs($this->admin)
            ->patch(route('admin.scholarship-applications.transition', $application), [
                'status' => 'document_verification',
            ])
            ->assertRedirect();

        // 6. Admin approves — notification + audit with record id
        $this->actingAs($this->admin)
            ->patch(route('admin.scholarship-applications.transition', $application), [
                'status' => 'approved',
            ])
            ->assertRedirect();

        $this->assertEquals('approved', $application->fresh()->getRawOriginal('status'));
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->studentA->id,
            'type' => 'scholarship.application_approved',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'Application Approved',
            'record_type' => 'ScholarshipApplication',
            'record_id' => $application->id,
        ]);
        $this->assertDatabaseHas('application_status_histories', [
            'scholarship_application_id' => $application->id,
            'new_status' => 'approved',
        ]);
    }

    public function test_student_cannot_access_or_modify_another_students_application_or_documents(): void
    {
        Storage::fake('local');

        $this->actingAs($this->studentA)
            ->post(route('student.applications.store', $this->scholarship));
        $application = ScholarshipApplication::where('student_profile_id', $this->profileA->id)->first();

        $file = UploadedFile::fake()->create('cor.pdf', 100, 'application/pdf');
        $this->actingAs($this->studentA)
            ->post(route('student.documents.store', $application), [
                'scholarship_requirement_id' => $this->scholarship->requirements->first()->id,
                'document' => $file,
            ]);
        $document = ApplicationDocument::first();

        // Student B cannot view, upload to, or download from student A's application
        $this->actingAs($this->studentB)
            ->get(route('student.applications.show', $application))
            ->assertForbidden();

        $this->actingAs($this->studentB)
            ->post(route('student.documents.store', $application), [
                'scholarship_requirement_id' => $this->scholarship->requirements->first()->id,
                'document' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'),
            ])
            ->assertForbidden();

        $this->actingAs($this->studentB)
            ->get(route('student.documents.download', $document))
            ->assertForbidden();
    }

    public function test_counselor_cannot_access_application_review_routes(): void
    {
        $this->actingAs($this->studentA)
            ->post(route('student.applications.store', $this->scholarship));
        $application = ScholarshipApplication::first();

        $this->actingAs($this->counselor)
            ->get(route('admin.scholarship-applications.index'))
            ->assertForbidden();

        $this->actingAs($this->counselor)
            ->patch(route('admin.scholarship-applications.transition', $application), [
                'status' => 'under_review',
            ])
            ->assertForbidden();
    }
}
