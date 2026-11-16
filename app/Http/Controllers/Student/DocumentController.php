<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadDocumentRequest;
use App\Models\ApplicationDocument;
use App\Models\ScholarshipApplication;
use App\Services\AuditLogService;
use App\Support\DocumentStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentController extends Controller
{
    public function store(UploadDocumentRequest $request, ScholarshipApplication $scholarshipApplication): RedirectResponse
    {
        $this->authorizeOwnership($scholarshipApplication);

        if (! $scholarshipApplication->allowsDocumentUpload()) {
            return back()->withErrors(['error' => 'Documents can only be uploaded while the application is in draft status.']);
        }

        $requirementId = $request->input('scholarship_requirement_id');

        // Verify requirement belongs to this scholarship
        $requirement = $scholarshipApplication->scholarship->requirements()->find($requirementId);
        if (! $requirement) {
            return back()->withErrors(['error' => 'Invalid requirement selected.']);
        }

        // Check for existing document for this requirement
        $existingDoc = $scholarshipApplication->documents()
            ->where('scholarship_requirement_id', $requirementId)
            ->first();

        if ($existingDoc) {
            // Replace: delete old file
            DocumentStorage::delete($existingDoc->file_path);
            $existingDoc->delete();
        }

        $file = $request->file('document');
        $extension = $file->getClientOriginalExtension();
        $storagePath = "scholarship-applications/{$scholarshipApplication->id}/".Str::uuid().".{$extension}";

        $path = $file->storeAs(
            dirname($storagePath),
            basename($storagePath),
            'local'
        );

        if ($path === false) {
            return back()->withErrors(['error' => 'The document could not be stored. Please try again.']);
        }

        $document = ApplicationDocument::create([
            'scholarship_application_id' => $scholarshipApplication->id,
            'scholarship_requirement_id' => $requirementId,
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'verification_status' => ApplicationDocument::VERIFICATION_PENDING,
        ]);

        AuditLogService::log(
            action: 'Document Uploaded',
            module: 'Scholarship Application',
            recordType: 'ApplicationDocument',
            recordId: $document->id,
            description: "Document uploaded for application '{$scholarshipApplication->application_number}', requirement '{$requirement->requirement_name}'.",
            request: $request
        );

        return back()->with('status', 'Document uploaded successfully.');
    }

    public function destroy(ScholarshipApplication $scholarshipApplication, ApplicationDocument $applicationDocument): RedirectResponse
    {
        $this->authorizeOwnership($scholarshipApplication);

        if (! $scholarshipApplication->allowsDocumentUpload()) {
            return back()->withErrors(['error' => 'Documents can only be removed while the application is in draft status.']);
        }

        if ($applicationDocument->scholarship_application_id !== $scholarshipApplication->id) {
            abort(403);
        }

        DocumentStorage::delete($applicationDocument->file_path);
        $applicationDocument->delete();

        AuditLogService::log(
            action: 'Document Removed',
            module: 'Scholarship Application',
            recordType: 'ApplicationDocument',
            recordId: $applicationDocument->id,
            description: "Document removed from application '{$scholarshipApplication->application_number}'.",
            request: request()
        );

        return back()->with('status', 'Document removed.');
    }

    public function download(ApplicationDocument $applicationDocument): BinaryFileResponse|RedirectResponse
    {
        $user = auth()->user();
        $application = $applicationDocument->application;

        if (! $application) {
            abort(404);
        }

        // Students can only download their own documents
        if ($user->isStudent()) {
            $profile = $user->studentProfile;
            if (! $profile || $application->student_profile_id !== $profile->id) {
                abort(403);
            }
        } elseif (! $user->isAdmin()) {
            abort(403);
        }

        $response = DocumentStorage::download(
            $applicationDocument->file_path,
            $applicationDocument->original_filename,
            $applicationDocument->mime_type,
        );

        if ($response === null) {
            return back()->with('error', 'The attached file could not be found. Please upload the document again.');
        }

        return $response;
    }

    protected function authorizeOwnership(ScholarshipApplication $application): void
    {
        $profile = auth()->user()->studentProfile;
        if (! $profile || $application->student_profile_id !== $profile->id) {
            abort(403, 'You are not authorized to access this application.');
        }
    }
}
