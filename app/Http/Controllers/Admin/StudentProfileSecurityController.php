<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StudentProfile;
use App\Services\AuditLogService;
use App\Services\StudentProfilePasscodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class StudentProfileSecurityController extends Controller
{
    public function __construct(
        private StudentProfilePasscodeService $passcodes,
    ) {}

    /**
     * Unlock a passcode-protected student profile for the current session.
     */
    public function unlock(Request $request, StudentProfile $studentProfile): RedirectResponse
    {
        Gate::authorize('manage-users');

        $validated = $request->validate([
            'passcode' => ['required', 'string', 'max:'.StudentProfilePasscodeService::MAX_LENGTH],
        ], [
            'passcode.required' => 'Enter the student profile passcode.',
        ]);

        if (! $this->passcodes->hasPasscode($studentProfile)) {
            return back()->withErrors(['passcode' => 'This profile does not have a passcode configured.']);
        }

        if (! $this->passcodes->attemptUnlock($studentProfile, $validated['passcode'], $request)) {
            return back()->withErrors([
                'passcode' => 'The passcode you entered is incorrect. Please try again.',
            ]);
        }

        return redirect()->route('admin.student-profiles.show', $studentProfile)
            ->with('status', 'Protected profile details unlocked for this session.');
    }

    /**
     * Lock a student profile again for the current session.
     */
    public function lock(Request $request, StudentProfile $studentProfile): RedirectResponse
    {
        Gate::authorize('manage-users');

        $this->passcodes->lock($studentProfile, $request);

        return redirect()->route('admin.student-profiles.show', $studentProfile)
            ->with('status', 'Protected profile details locked.');
    }

    /**
     * Reset a profile passcode. Reserved for authorized administrators so a
     * forgotten passcode can be recovered without exposing the old value.
     */
    public function reset(Request $request, StudentProfile $studentProfile): RedirectResponse
    {
        Gate::authorize('manage-users');

        $validated = $request->validate([
            'passcode' => [
                'required',
                'string',
                'min:'.StudentProfilePasscodeService::MIN_LENGTH,
                'max:'.StudentProfilePasscodeService::MAX_LENGTH,
            ],
            'passcode_confirmation' => ['nullable', 'string', 'max:'.StudentProfilePasscodeService::MAX_LENGTH],
        ]);

        if (
            filled($validated['passcode_confirmation'] ?? null)
            && $validated['passcode'] !== $validated['passcode_confirmation']
        ) {
            throw ValidationException::withMessages([
                'passcode_confirmation' => 'The passcode confirmation does not match.',
            ]);
        }

        $this->passcodes->setPasscode($studentProfile, $validated['passcode']);
        $this->passcodes->unlock($studentProfile, $request);

        AuditLogService::log(
            action: 'Student Profile Passcode Reset',
            module: 'Student Profile',
            recordType: 'StudentProfile',
            recordId: $studentProfile->id,
            description: "Administrator reset the profile passcode for '{$studentProfile->first_name} {$studentProfile->last_name}' ({$studentProfile->student_number}).",
            request: $request,
        );

        return redirect()->route('admin.student-profiles.show', $studentProfile)
            ->with('status', 'Profile passcode reset successfully.');
    }
}
