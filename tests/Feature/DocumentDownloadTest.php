<?php

namespace Tests\Feature;

use App\Models\ApplicationDocument;
use App\Models\Role;
use App\Models\Scholarship;
use App\Models\ScholarshipApplication;
use App\Models\ServiceRequestDocument;
use App\Models\ServiceType;
use App\Models\StudentProfile;
use App\Models\StudentServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentDownloadTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $student;

    protected StudentProfile $profile;

    protected Scholarship $scholarship;

    protected ScholarshipApplication $application;

    protected ServiceType $serviceType;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $adminRole = Role::firstOrCreate(['name' => Role::ADMIN], ['label' => 'Administrator']);
        $studentRole = Role::firstOrCreate(['name' => Role::STUDENT], ['label' => 'Student']);

        $this->admin = User::factory()->create(['role_id' => $adminRole->id, 'is_active' => true]);
        $this->student = User::factory()->create(['role_id' => $studentRole->id, 'is_active' => true]);
        $this->profile = StudentProfile::factory()->create(['user_id' => $this->student->id]);

        $this->scholarship = Scholarship::create([
            'title' => 'Attachment Test Scholarship',
            'description' => 'Used to verify document downloads.',
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

        $this->application = ScholarshipApplication::create([
            'student_profile_id' => $this->profile->id,
            'scholarship_id' => $this->scholarship->id,
            'application_number' => 'SCH-2026-999999',
            'status' => 'draft',
        ]);

        $this->serviceType = ServiceType::create([
            'name' => 'Attachment Test Service',
            'description' => 'Used to verify attachment uploads.',
            'is_active' => true,
            'display_order' => 1,
        ]);
    }

    public function test_application_document_downloads_from_canonical_path(): void
    {
        Storage::disk('local')->put('scholarship-applications/1/cor.pdf', 'document-content');

        $document = ApplicationDocument::create([
            'scholarship_application_id' => $this->application->id,
            'scholarship_requirement_id' => $this->scholarship->requirements->first()->id,
            'file_path' => 'scholarship-applications/1/cor.pdf',
            'original_filename' => 'cor.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 16,
            'verification_status' => ApplicationDocument::VERIFICATION_PENDING,
        ]);

        $this->actingAs($this->student)
            ->get(route('student.documents.download', $document))
            ->assertOk();
    }

    public function test_legacy_double_private_service_request_document_still_downloads(): void
    {
        $serviceRequest = StudentServiceRequest::create([
            'request_number' => 'SR-2026-999999',
            'student_profile_id' => $this->profile->id,
            'service_type_id' => $this->serviceType->id,
            'description' => 'Legacy attachment download test.',
            'status' => StudentServiceRequest::STATUS_SUBMITTED,
            'submitted_at' => now(),
        ]);

        // Historical uploads wrote "private/..." to a disk rooted at
        // storage/app/private, producing storage/app/private/private/...
        $legacyPath = "private/student-service-requests/{$serviceRequest->id}/legacy.jpg";
        Storage::disk('local')->put($legacyPath, 'legacy-content');

        $document = ServiceRequestDocument::create([
            'student_service_request_id' => $serviceRequest->id,
            'file_path' => $legacyPath,
            'original_filename' => 'legacy.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 14,
            'uploaded_by' => $this->student->id,
        ]);

        $this->actingAs($this->student)
            ->get(route('student.services.documents.download', [$serviceRequest, $document]))
            ->assertOk();

        $this->actingAs($this->admin)
            ->get(route('admin.student-service-requests.documents.download', [$serviceRequest, $document]))
            ->assertOk();
    }

    public function test_new_service_request_uploads_use_canonical_path_without_private_prefix(): void
    {
        $requirement = $this->serviceType->requirements()->create([
            'name' => 'Valid ID',
            'is_required' => true,
            'display_order' => 1,
        ]);

        $this->actingAs($this->student)->post(route('student.services.store'), [
            'service_type_id' => $this->serviceType->id,
            'description' => 'Please process this attachment upload test.',
            "requirement_{$requirement->id}" => UploadedFile::fake()->create('id.pdf', 10, 'application/pdf'),
        ])->assertRedirect();

        $document = ServiceRequestDocument::firstOrFail();

        $this->assertStringStartsWith('student-service-requests/', $document->file_path);
        $this->assertStringNotContainsString('private/private/', $document->file_path);
        $this->assertTrue(Storage::disk('local')->exists($document->file_path));

        $this->actingAs($this->student)
            ->get(route('student.services.documents.download', [$document->student_service_request_id, $document]))
            ->assertOk();
    }

    public function test_missing_attachment_redirects_with_a_friendly_error_instead_of_404(): void
    {
        $document = ApplicationDocument::create([
            'scholarship_application_id' => $this->application->id,
            'scholarship_requirement_id' => $this->scholarship->requirements->first()->id,
            'file_path' => 'scholarship-applications/1/missing.pdf',
            'original_filename' => 'missing.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 10,
            'verification_status' => ApplicationDocument::VERIFICATION_PENDING,
        ]);

        $this->actingAs($this->student)
            ->from(route('student.applications.show', $this->application))
            ->get(route('student.documents.download', $document))
            ->assertRedirect(route('student.applications.show', $this->application))
            ->assertSessionHas('error');
    }
}
