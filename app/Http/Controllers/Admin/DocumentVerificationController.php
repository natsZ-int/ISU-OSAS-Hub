<?php

namespace App\Http\Controllers\Admin;

use App\Enums\NotificationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\VerifyDocumentRequest;
use App\Models\ApplicationDocument;
use App\Services\AuditLogService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class DocumentVerificationController extends Controller
{
    public function __construct(protected NotificationService $notificationService) {}

    public function update(VerifyDocumentRequest $request, ApplicationDocument $applicationDocument): RedirectResponse
    {
        Gate::authorize('manage-users');

        $newStatus = $request->input('verification_status');

        if ($newStatus === ApplicationDocument::VERIFICATION_REJECTED && ! $request->filled('verification_remarks')) {
            return back()->withErrors(['error' => 'Remarks are required when rejecting a document.']);
        }

        $previousStatus = $applicationDocument->verification_status;

        $applicationDocument->update([
            'verification_status' => $newStatus,
            'verification_remarks' => $request->input('verification_remarks'),
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        $actionLabel = $newStatus === 'verified' ? 'Document Verified' : 'Document Rejected';

        AuditLogService::log(
            action: $actionLabel,
            module: 'Scholarship Review',
            recordType: 'ApplicationDocument',
            recordId: $applicationDocument->id,
            description: "Document for application '{$applicationDocument->application->application_number}' marked as '{$newStatus}'.",
            request: $request
        );

        // Only notify on a genuine status change so repeat saves stay quiet.
        if ($applicationDocument->verification_status !== $previousStatus) {
            $this->notifyStudent($applicationDocument, $newStatus);
        }

        return back()->with('status', "Document marked as {$newStatus}.");
    }

    protected function notifyStudent(ApplicationDocument $document, string $newStatus): void
    {
        $application = $document->application;
        $studentUser = $application?->studentProfile?->user;

        if (! $application || ! $studentUser) {
            return;
        }

        $accepted = $newStatus === ApplicationDocument::VERIFICATION_VERIFIED;
        $requirementName = $document->requirement?->requirement_name ?? 'your document';

        $type = $accepted
            ? NotificationType::SCHOLARSHIP_DOCUMENT_APPROVED
            : NotificationType::SCHOLARSHIP_DOCUMENT_REJECTED;
        $title = $accepted ? 'Document Accepted' : 'Document Rejected';
        $message = $accepted
            ? "Your document for '{$requirementName}' in application {$application->application_number} has been accepted."
            : "Your document for '{$requirementName}' in application {$application->application_number} was rejected during verification.";

        $reason = ! $accepted && filled($document->verification_remarks)
            ? trim((string) $document->verification_remarks)
            : null;

        $this->notificationService->notifyUser(
            $studentUser,
            $type,
            $title,
            $message,
            'ScholarshipApplication',
            $application->id,
            [
                'reference' => $application->application_number,
                'reference_label' => 'Application No.',
                'status' => $accepted ? 'Document Accepted' : 'Document Rejected',
                'next_step' => $accepted
                    ? 'No action is required for this document. We will notify you of further updates.'
                    : 'Please review the remarks in your application details and contact OSAS for guidance.',
                'url' => route('student.applications.show', $application->id),
                'reason' => $reason,
            ],
            sendEmail: true,
        );
    }
}
