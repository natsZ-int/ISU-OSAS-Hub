<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use App\Services\StudentProfilePasscodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProfileSecurityController extends Controller
{
    public function __construct(
        private StudentProfilePasscodeService $passcodes,
    ) {}

    /**
     * Show the profile security / passcode page.
     */
    public function show(Request $request): View|RedirectResponse
    {
        $profile = $request->user()->studentProfile;

        if (! $profile) {
            return redirect()->route('student.dashboard')
                ->with('error', 'Your student profile has not been created yet. Please contact the OSAS administrator.');
        }

        Gate::authorize('view', $profile);

        return view('student.profile.security', [
            'profile' => $profile,
            'hasPasscode' => $this->passcodes->hasPasscode($profile),
            'isUnlocked' => $this->passcodes->isUnlocked($profile, $request),
        ]);
    }

    /**
     * Set or change the profile passcode.
     */
    public function store(Request $request): RedirectResponse
    {
        $profile = $request->user()->studentProfile;

        if (! $profile) {
            return redirect()->route('student.dashboard')
                ->with('error', 'Your student profile has not been created yet. Please contact the OSAS administrator.');
        }

        Gate::authorize('update', $profile);

        $validated = $request->validate([
            'passcode' => [
                'required',
                'string',
                'min:'.StudentProfilePasscodeService::MIN_LENGTH,
                'max:'.StudentProfilePasscodeService::MAX_LENGTH,
            ],
            'passcode_confirmation' => ['nullable', 'string', 'max:'.StudentProfilePasscodeService::MAX_LENGTH],
            'current_passcode' => ['nullable', 'string', 'max:'.StudentProfilePasscodeService::MAX_LENGTH],
        ], [
            'passcode.required' => 'Please enter a passcode.',
            'passcode.min' => 'The passcode must be at least '.StudentProfilePasscodeService::MIN_LENGTH.' characters.',
            'passcode.max' => 'The passcode may not exceed '.StudentProfilePasscodeService::MAX_LENGTH.' characters.',
        ]);

        if (
            filled($validated['passcode_confirmation'] ?? null)
            && $validated['passcode'] !== $validated['passcode_confirmation']
        ) {
            throw ValidationException::withMessages([
                'passcode_confirmation' => 'The passcode confirmation does not match.',
            ]);
        }

        // When a passcode already exists, a viewer who has not unlocked the
        // profile must prove knowledge of the current passcode before changing it.
        if (
            $this->passcodes->hasPasscode($profile)
            && ! $this->passcodes->isUnlocked($profile, $request)
            && ! $this->passcodes->verify($profile, $validated['current_passcode'] ?? null)
        ) {
            throw ValidationException::withMessages([
                'current_passcode' => 'The current passcode is incorrect.',
            ]);
        }

        $hadPasscode = $this->passcodes->hasPasscode($profile);

        $this->passcodes->setPasscode($profile, $validated['passcode']);
        $this->passcodes->unlock($profile, $request);

        AuditLogService::log(
            action: $hadPasscode ? 'Student Profile Passcode Changed' : 'Student Profile Passcode Set',
            module: 'Student Profile',
            recordType: 'StudentProfile',
            recordId: $profile->id,
            description: "Student '{$profile->first_name} {$profile->last_name}' ({$profile->student_number}) "
                .($hadPasscode ? 'changed' : 'set').' their profile passcode.',
            request: $request,
        );

        return redirect()->route('student.profile.show')
            ->with('status', $hadPasscode
                ? 'Your profile passcode has been updated.'
                : 'Your profile is now protected with a passcode.');
    }

    /**
     * Unlock the profile for the current session.
     */
    public function unlock(Request $request): RedirectResponse
    {
        $profile = $request->user()->studentProfile;

        if (! $profile) {
            return redirect()->route('student.dashboard')
                ->with('error', 'Your student profile has not been created yet. Please contact the OSAS administrator.');
        }

        Gate::authorize('view', $profile);

        $validated = $request->validate([
            'passcode' => ['required', 'string', 'max:'.StudentProfilePasscodeService::MAX_LENGTH],
        ], [
            'passcode.required' => 'Please enter your profile passcode.',
        ]);

        if (! $this->passcodes->hasPasscode($profile)) {
            return back()->withErrors(['passcode' => 'No passcode is configured for this profile.']);
        }

        if (! $this->passcodes->attemptUnlock($profile, $validated['passcode'], $request)) {
            return back()->withErrors([
                'passcode' => 'The passcode you entered is incorrect. Please try again.',
            ]);
        }

        return redirect()->route('student.profile.show')
            ->with('status', 'Profile unlocked for this session.');
    }

    /**
     * Re-lock the profile for the current session.
     */
    public function lock(Request $request): RedirectResponse
    {
        $profile = $request->user()->studentProfile;

        if (! $profile) {
            return redirect()->route('student.dashboard');
        }

        Gate::authorize('view', $profile);

        $this->passcodes->lock($profile, $request);

        return redirect()->route('student.profile.show')
            ->with('status', 'Profile locked. Enter your passcode to view protected details again.');
    }

    /**
     * Remove passcode protection from the profile.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $profile = $request->user()->studentProfile;

        if (! $profile) {
            return redirect()->route('student.dashboard');
        }

        Gate::authorize('update', $profile);

        if (
            $this->passcodes->hasPasscode($profile)
            && ! $this->passcodes->isUnlocked($profile, $request)
        ) {
            $request->validate([
                'passcode' => ['required', 'string', 'max:'.StudentProfilePasscodeService::MAX_LENGTH],
            ], [
                'passcode.required' => 'Enter your current passcode to remove protection.',
            ]);

            if (! $this->passcodes->verify($profile, $request->input('passcode'))) {
                return back()->withErrors(['passcode' => 'The passcode you entered is incorrect.']);
            }
        }

        $this->passcodes->clearPasscode($profile, $request);

        AuditLogService::log(
            action: 'Student Profile Passcode Removed',
            module: 'Student Profile',
            recordType: 'StudentProfile',
            recordId: $profile->id,
            description: "Student '{$profile->first_name} {$profile->last_name}' ({$profile->student_number}) removed profile passcode protection.",
            request: $request,
        );

        return redirect()->route('student.profile.show')
            ->with('status', 'Profile passcode protection has been removed.');
    }
}
