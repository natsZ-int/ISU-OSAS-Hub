<?php

namespace App\Http\Controllers\Staff;

use App\Enums\EnrollmentStatus;
use App\Enums\EnrollmentVerificationStatus;
use App\Enums\NotificationType;
use App\Http\Controllers\Controller;
use App\Models\StudentEnrollmentVerification;
use App\Services\AuditLogService;
use App\Services\NotificationService;
use App\Services\ScholarshipAnnouncementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EnrollmentVerificationController extends Controller
{
    public function __construct(
        private NotificationService $notificationService,
        private ScholarshipAnnouncementService $announcements,
    ) {}

    /**
     * Staff queue of enrollment verification requests.
     */
    public function index(Request $request): View
    {
        Gate::authorize('manage-enrollment-verifications');

        $query = StudentEnrollmentVerification::with(['studentProfile.user'])
            ->orderByDesc('created_at');

        if ($search = $request->filled('search') ? $request->input('search') : null) {
            $query->whereHas('studentProfile', function ($q) use ($search) {
                $q->where('student_number', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($uq) => $uq->where('email', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->filled('status') ? $request->input('status') : null) {
            if (in_array($status, EnrollmentVerificationStatus::values(), true)) {
                $query->where('status', $status);
            }
        }

        if ($year = $request->filled('academic_year') ? $request->input('academic_year') : null) {
            $query->where('academic_year', $year);
        }

        if ($semester = $request->filled('semester') ? $request->input('semester') : null) {
            $query->where('semester', $semester);
        }

        $verifications = $query->paginate(15)->withQueryString();

        $academicYears = StudentEnrollmentVerification::query()
            ->distinct()
            ->orderBy('academic_year')
            ->pluck('academic_year');

        return view('staff.enrollment-verifications.index', [
            'verifications' => $verifications,
            'academicYears' => $academicYears,
            'semesters' => config('academic.semesters'),
            'statuses' => EnrollmentVerificationStatus::cases(),
        ]);
    }

    /**
     * Show a single verification request and its history.
     */
    public function show(StudentEnrollmentVerification $enrollmentVerification): View
    {
        Gate::authorize('manage-enrollment-verifications');

        $enrollmentVerification->load(['studentProfile.user', 'verifier']);

        $history = StudentEnrollmentVerification::where('student_profile_id', $enrollmentVerification->student_profile_id)
            ->orderByDesc('created_at')
            ->get();

        return view('staff.enrollment-verifications.show', [
            'verification' => $enrollmentVerification,
            'history' => $history,
            'semesters' => config('academic.semesters'),
        ]);
    }

    /**
     * Record a staff verification decision.
     */
    public function update(Request $request, StudentEnrollmentVerification $enrollmentVerification): RedirectResponse
    {
        Gate::authorize('manage-enrollment-verifications');

        $validated = $request->validate([
            'status' => ['required', Rule::in([
                EnrollmentVerificationStatus::VERIFIED->value,
                EnrollmentVerificationStatus::REJECTED->value,
                EnrollmentVerificationStatus::UNABLE_TO_VERIFY->value,
            ])],
            'academic_year' => ['required', 'string', 'max:20'],
            'semester' => ['required', Rule::in(config('academic.semesters'))],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $requiresReason = in_array($validated['status'], [
            EnrollmentVerificationStatus::REJECTED->value,
            EnrollmentVerificationStatus::UNABLE_TO_VERIFY->value,
        ], true);

        if ($requiresReason && blank($validated['notes'] ?? null)) {
            return back()->withErrors(['notes' => 'A reason is required when rejecting a request or marking it unable to verify.']);
        }

        $profile = $enrollmentVerification->studentProfile;

        if (! $profile) {
            return back()->withErrors(['error' => 'The student profile for this request no longer exists.']);
        }

        $statusMap = [
            EnrollmentVerificationStatus::VERIFIED->value => EnrollmentStatus::ENROLLED->value,
            EnrollmentVerificationStatus::REJECTED->value => EnrollmentStatus::NOT_ENROLLED->value,
            EnrollmentVerificationStatus::UNABLE_TO_VERIFY->value => EnrollmentStatus::UNKNOWN->value,
        ];

        DB::transaction(function () use ($enrollmentVerification, $validated, $profile, $statusMap) {
            $enrollmentVerification->update([
                'status' => $validated['status'],
                'academic_year' => $validated['academic_year'],
                'semester' => $validated['semester'],
                'notes' => $validated['notes'] ?? null,
                'verified_by' => auth()->id(),
                'verified_at' => now(),
            ]);

            $profile->update([
                'enrollment_status' => $statusMap[$validated['status']],
            ]);
        });

        AuditLogService::log(
            action: 'enrollment_verification_decision',
            module: 'enrollment_verification',
            recordType: 'StudentEnrollmentVerification',
            recordId: $enrollmentVerification->id,
            description: sprintf(
                'Enrollment verification for %s (%s) marked %s for %s %s.',
                $profile->first_name.' '.$profile->last_name,
                $profile->student_number,
                $validated['status'],
                $validated['academic_year'],
                $validated['semester'],
            ),
            request: $request,
        );

        $student = $profile->user;

        if ($student) {
            $notificationType = match ($validated['status']) {
                EnrollmentVerificationStatus::VERIFIED->value => NotificationType::ACCOUNT_ENROLLMENT_VERIFIED,
                EnrollmentVerificationStatus::REJECTED->value => NotificationType::ACCOUNT_ENROLLMENT_REJECTED,
                EnrollmentVerificationStatus::UNABLE_TO_VERIFY->value => NotificationType::ACCOUNT_ENROLLMENT_UNABLE_TO_VERIFY,
            };

            $message = match ($validated['status']) {
                EnrollmentVerificationStatus::VERIFIED->value => 'Your enrollment for '.$validated['academic_year'].' '.$validated['semester']
                    .' semester has been verified by OSAS. Student services are now available to you.',
                EnrollmentVerificationStatus::REJECTED->value => 'Your enrollment verification for '.$validated['academic_year'].' '.$validated['semester']
                    .' was rejected. Please contact the OSAS office for details.',
                default => 'Your enrollment for '.$validated['academic_year'].' '.$validated['semester']
                    .' could not be verified. Please contact the OSAS office.',
            };

            $this->notificationService->notifyUser(
                $student,
                $notificationType,
                'Enrollment Verification Update',
                $message,
                'StudentEnrollmentVerification',
                $enrollmentVerification->id,
            );
        }

        // Verifying enrollment changes the eligibility inputs (enrollment
        // status), so announce any newly matched open scholarships.
        $this->announcements->announceToStudent($profile);

        return redirect()->route('enrollment-verifications.index')
            ->with('status', 'Verification decision recorded.');
    }
}
