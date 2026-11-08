<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateOwnStudentProfileRequest;
use App\Services\AuditLogService;
use App\Services\ScholarshipAnnouncementService;
use App\Services\StudentProfilePasscodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(
        private ScholarshipAnnouncementService $announcements,
        private StudentProfilePasscodeService $passcodes,
    ) {}

    /**
     * Display the student's own profile.
     */
    public function show(): View|RedirectResponse
    {
        $profile = auth()->user()->studentProfile;

        if (! $profile) {
            return redirect()->route('student.dashboard')
                ->with('error', 'Your student profile has not been created yet. Please contact the OSAS administrator.');
        }

        Gate::authorize('view', $profile);

        $profile->load([
            'user',
            'enrollmentVerifications' => fn ($query) => $query->orderByDesc('created_at'),
        ]);

        return view('student.profile.show', [
            'profile' => $profile,
            'hasPasscode' => $this->passcodes->hasPasscode($profile),
            'sensitiveUnlocked' => $this->passcodes->isUnlocked($profile, request()),
        ]);
    }

    /**
     * Show the form for editing the student's own profile.
     */
    public function edit(): View|RedirectResponse
    {
        $profile = auth()->user()->studentProfile;

        if (! $profile) {
            return redirect()->route('student.dashboard')
                ->with('error', 'Your student profile has not been created yet. Please contact the OSAS administrator.');
        }

        Gate::authorize('update', $profile);

        if (! $this->passcodes->isUnlocked($profile, request())) {
            return redirect()->route('student.profile.show')
                ->withErrors(['passcode' => 'Enter your profile passcode to unlock protected information before editing.']);
        }

        return view('student.profile.edit', compact('profile'));
    }

    /**
     * Update the student's own profile.
     *
     * Only student-editable fields are accepted; the student number and
     * enrollment status remain system-managed.
     */
    public function update(UpdateOwnStudentProfileRequest $request): RedirectResponse
    {
        $profile = auth()->user()->studentProfile;

        if (! $profile) {
            return redirect()->route('student.dashboard')
                ->with('error', 'Your student profile has not been created yet. Please contact the OSAS administrator.');
        }

        Gate::authorize('update', $profile);

        if (! $this->passcodes->isUnlocked($profile, $request)) {
            $suppliedPasscode = $request->input('current_passcode', $request->input('passcode'));

            if (! $this->passcodes->verify($profile, is_string($suppliedPasscode) ? $suppliedPasscode : null)) {
                return redirect()->route('student.profile.show')
                    ->withErrors(['passcode' => 'Enter your profile passcode to unlock protected information before editing.']);
            }

            $this->passcodes->unlock($profile, $request);
        }

        $profile->update($request->validated());

        AuditLogService::log(
            action: 'Student Profile Updated',
            module: 'Student Profile',
            recordType: 'StudentProfile',
            recordId: $profile->id,
            description: "Student '{$profile->first_name} {$profile->last_name}' ({$profile->student_number}) updated their profile.",
            request: $request,
        );

        // A profile change (e.g. an improved GWA) can make the student newly
        // eligible for open scholarships, so tell them automatically.
        $this->announcements->announceToStudent($profile);

        return redirect()->route('student.profile.show')
            ->with('status', 'Your profile has been updated successfully.');
    }
}
