<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\VerifyDocumentRequest;
use App\Models\ApplicationDocument;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class DocumentVerificationController extends Controller
{
    public function update(VerifyDocumentRequest $request, ApplicationDocument $applicationDocument): RedirectResponse
    {
        Gate::authorize('manage-users');

        $newStatus = $request->input('verification_status');

        if ($newStatus === ApplicationDocument::VERIFICATION_REJECTED && ! $request->filled('verification_remarks')) {
            return back()->withErrors(['error' => 'Remarks are required when rejecting a document.']);
        }

        $applicationDocument->update([
            'verification_status'  => $newStatus,
            'verification_remarks' => $request->input('verification_remarks'),
            'verified_by'          => $request->user()->id,
            'verified_at'          => now(),
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

        return back()->with('success', "Document marked as {$newStatus}.");
    }
}