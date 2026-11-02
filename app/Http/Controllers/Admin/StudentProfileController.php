<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EnrollmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateStudentProfileRequest;
use App\Models\StudentProfile;
use App\Services\AuditLogService;
use App\Services\ScholarshipAnnouncementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StudentProfileController extends Controller
{
    public function __construct(
        private ScholarshipAnnouncementService $announcements,
    ) {}

    /**
     * Paginated list of student profiles with search and filters.
     */
    public function index(Request $request): View
    {
        Gate::authorize('manage-users');

        $query = StudentProfile::with(['user.role']);

        if ($search = $request->filled('search') ? $request->input('search') : null) {
            $query->where(function ($q) use ($search) {
                $q->where('student_number', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($uq) => $uq->where('email', 'like', "%{$search}%"));
            });
        }

        if ($college = $request->filled('college') ? $request->input('college') : null) {
            $query->where('college', $college);
        }

        if ($yearLevel = $request->filled('year_level') ? $request->input('year_level') : null) {
            $query->where('year_level', $yearLevel);
        }

        if ($status = $request->filled('enrollment_status') ? $request->input('enrollment_status') : null) {
            if (in_array($status, EnrollmentStatus::values(), true)) {
                $query->where('enrollment_status', $status);
            }
        }

        $profiles = $query->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(15)
            ->withQueryString();

        $colleges = StudentProfile::query()
            ->whereNotNull('college')
            ->where('college', '!=', '')
            ->distinct()
            ->orderBy('college')
            ->pluck('college');

        return view('admin.student-profiles.index', [
            'profiles' => $profiles,
            'colleges' => $colleges,
            'yearLevels' => range(1, 6),
            'enrollmentStatuses' => EnrollmentStatus::cases(),
        ]);
    }

    /**
     * Display a single student profile.
     */
    public function show(StudentProfile $studentProfile): View
    {
        Gate::authorize('manage-users');

        $studentProfile->load([
            'user',
            'enrollmentVerifications' => fn ($query) => $query->orderByDesc('created_at'),
        ]);

        return view('admin.student-profiles.show', [
            'profile' => $studentProfile,
            'history' => $studentProfile->enrollmentVerifications,
        ]);
    }

    /**
     * Show the form for editing a student profile.
     */
    public function edit(StudentProfile $studentProfile): View
    {
        Gate::authorize('manage-users');

        $studentProfile->load('user');

        return view('admin.student-profiles.edit', [
            'profile' => $studentProfile,
            'enrollmentStatuses' => EnrollmentStatus::cases(),
        ]);
    }

    /**
     * Update a student profile.
     */
    public function update(UpdateStudentProfileRequest $request, StudentProfile $studentProfile): RedirectResponse
    {
        Gate::authorize('manage-users');

        $studentProfile->update($request->validated());

        AuditLogService::log(
            action: 'Student Profile Updated',
            module: 'Student Profile',
            recordType: 'StudentProfile',
            recordId: $studentProfile->id,
            description: "Administrator updated the profile of '{$studentProfile->first_name} {$studentProfile->last_name}' ({$studentProfile->student_number}).",
            request: $request,
        );

        // Staff edits (enrollment status, year level, program, GWA) can make
        // the student newly eligible for open scholarships.
        $this->announcements->announceToStudent($studentProfile);

        return redirect()->route('admin.student-profiles.show', $studentProfile)
            ->with('status', "Profile for '{$studentProfile->first_name} {$studentProfile->last_name}' updated successfully.");
    }
}
