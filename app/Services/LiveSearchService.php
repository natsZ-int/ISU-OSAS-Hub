<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Models\AuditLog;
use App\Models\GuidanceAppointment;
use App\Models\Notification;
use App\Models\Scholarship;
use App\Models\ScholarshipApplication;
use App\Models\ServiceType;
use App\Models\StudentEnrollmentVerification;
use App\Models\StudentProfile;
use App\Models\StudentServiceRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Shared, role-aware suggestion engine for the system-wide quick search.
 *
 * Every public entry point is intentionally scoped to the caller's role:
 * students only ever see their own records, counselors only see their own
 * appointments, and administrators only reach this service through the
 * admin-only route group.
 */
class LiveSearchService
{
    public const DEFAULT_LIMIT = 8;

    public const MAX_LIMIT = 20;

    public const MAX_TERM_LENGTH = 200;

    public function __construct(
        protected ScholarshipSearchService $scholarshipSearch,
    ) {}

    // ── Entry points ────────────────────────────────────────────

    public function adminSearch(string $module, ?string $term, int $limit = self::DEFAULT_LIMIT): array
    {
        $term = $this->normalizeTerm($term);
        if ($term === null) {
            return [];
        }

        $limit = $this->clampLimit($limit);

        return match ($module) {
            'scholarships' => $this->adminScholarships($term, $limit),
            'applications' => $this->adminApplications($term, $limit),
            'students' => $this->adminStudents($term, $limit),
            'users' => $this->adminUsers($term, $limit),
            'service-requests' => $this->adminServiceRequests($term, $limit),
            'appointments' => $this->adminAppointments($term, $limit),
            'audit-logs' => $this->adminAuditLogs($term, $limit),
            'analytics' => $this->adminAnalytics($term, $limit),
            'analytics-scholarships' => $this->adminApplications($term, $limit),
            'analytics-guidance' => $this->adminAppointments($term, $limit),
            'analytics-services' => $this->adminServiceRequests($term, $limit),
            'global' => $this->adminGlobal($term, $limit),
            default => [],
        };
    }

    public function studentSearch(string $module, ?string $term, User $user, int $limit = self::DEFAULT_LIMIT): array
    {
        $term = $this->normalizeTerm($term);
        if ($term === null) {
            return [];
        }

        $limit = $this->clampLimit($limit);
        $profile = $user->studentProfile;

        return match ($module) {
            'scholarships' => $this->studentScholarships($term, $profile, $limit),
            'applications' => $this->studentApplications($term, $profile, $limit),
            'service-requests' => $this->studentServiceRequests($term, $profile, $limit),
            'appointments' => $this->studentAppointments($term, $profile, $limit),
            'services' => $this->studentServices($term, $limit),
            'counselors' => $this->studentCounselors($term, $limit),
            'global' => $this->studentGlobal($term, $profile, $limit),
            default => [],
        };
    }

    public function counselorSearch(string $module, ?string $term, User $user, int $limit = self::DEFAULT_LIMIT): array
    {
        $term = $this->normalizeTerm($term);
        if ($term === null) {
            return [];
        }

        $limit = $this->clampLimit($limit);

        return match ($module) {
            'appointments' => $this->counselorAppointments($term, $user, $limit),
            default => [],
        };
    }

    public function enrollmentSearch(?string $term, int $limit = self::DEFAULT_LIMIT): array
    {
        $term = $this->normalizeTerm($term);
        if ($term === null) {
            return [];
        }

        return $this->enrollmentVerifications($term, $this->clampLimit($limit));
    }

    public function notificationSearch(?string $term, User $user, int $limit = self::DEFAULT_LIMIT): array
    {
        $term = $this->normalizeTerm($term);
        if ($term === null) {
            return [];
        }

        return $this->notifications($term, $user, $this->clampLimit($limit));
    }

    public function normalizeTerm(?string $term): ?string
    {
        if ($term === null) {
            return null;
        }

        $trimmed = trim(preg_replace('/\s+/u', ' ', $term) ?? '');
        if ($trimmed === '') {
            return null;
        }

        return Str::limit($trimmed, self::MAX_TERM_LENGTH, '');
    }

    // ── Admin modules ───────────────────────────────────────────

    protected function adminScholarships(string $term, int $limit): array
    {
        $query = Scholarship::query()->withCount('applications');

        $this->applyColumnSearch(
            $query,
            ['title', 'provider', 'scholarship_type', 'description'],
            $term,
        );

        return $query->orderBy('title')
            ->limit($limit)
            ->get()
            ->map(fn (Scholarship $scholarship) => $this->result(
                id: $scholarship->id,
                title: $scholarship->title,
                subtitle: collect([$scholarship->provider, $scholarship->scholarship_type])->filter()->implode(' · '),
                meta: $scholarship->application_deadline
                    ? 'Deadline: '.$scholarship->application_deadline->format('M d, Y')
                    : null,
                url: route('admin.scholarships.edit', $scholarship),
                badge: ucfirst((string) $scholarship->status),
                badgeTone: $this->statusTone($scholarship->status),
            ))
            ->all();
    }

    protected function adminApplications(string $term, int $limit): array
    {
        $query = ScholarshipApplication::query()->with(['studentProfile', 'scholarship']);

        $this->applyColumnSearch(
            $query,
            ['application_number'],
            $term,
            [
                'studentProfile' => ['student_number', 'first_name', 'last_name'],
                'scholarship' => ['title', 'provider'],
            ],
        );

        return $query->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function (ScholarshipApplication $application) {
                $student = $application->studentProfile;

                return $this->result(
                    id: $application->id,
                    title: $application->application_number,
                    subtitle: $student
                        ? trim($student->first_name.' '.$student->last_name).' · '.$student->student_number
                        : 'Student unavailable',
                    meta: $application->scholarship?->title,
                    url: route('admin.scholarship-applications.show', $application),
                    badge: ucfirst(str_replace('_', ' ', (string) $application->status)),
                    badgeTone: $this->statusTone($application->status),
                );
            })
            ->all();
    }

    protected function adminStudents(string $term, int $limit): array
    {
        $query = StudentProfile::query()->with(['user']);

        $this->applyColumnSearch(
            $query,
            ['student_number', 'first_name', 'last_name', 'middle_name', 'program', 'college'],
            $term,
            ['user' => ['name', 'email']],
        );

        return $query->orderBy('last_name')
            ->orderBy('first_name')
            ->limit($limit)
            ->get()
            ->map(function (StudentProfile $profile) {
                $enrollment = EnrollmentStatus::tryFrom($profile->enrollment_status ?? 'unknown');

                return $this->result(
                    id: $profile->id,
                    title: trim($profile->first_name.' '.$profile->last_name),
                    subtitle: collect([$profile->student_number, $profile->program])->filter()->implode(' · '),
                    meta: $profile->college ?: $profile->user?->email,
                    url: route('admin.student-profiles.show', $profile),
                    badge: $enrollment?->label() ?? 'Unknown',
                    badgeTone: $this->enrollmentTone($profile->enrollment_status),
                );
            })
            ->all();
    }

    protected function adminUsers(string $term, int $limit): array
    {
        $query = User::query()->with(['role', 'studentProfile']);

        $this->applyColumnSearch(
            $query,
            ['name', 'email'],
            $term,
            [
                'role' => ['name', 'label'],
                'studentProfile' => ['student_number'],
            ],
        );

        return $query->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(fn (User $user) => $this->result(
                id: $user->id,
                title: $user->name,
                subtitle: $user->email,
                meta: collect([
                    $user->role?->label ?? $user->role?->name,
                    $user->studentProfile?->student_number,
                ])->filter()->implode(' · '),
                url: route('admin.users.edit', $user),
                badge: $user->is_active ? 'Active' : 'Inactive',
                badgeTone: $user->is_active ? 'success' : 'neutral',
            ))
            ->all();
    }

    protected function adminServiceRequests(string $term, int $limit): array
    {
        $query = StudentServiceRequest::query()->with(['studentProfile', 'serviceType']);

        $this->applyColumnSearch(
            $query,
            ['request_number', 'description', 'status'],
            $term,
            [
                'studentProfile' => ['student_number', 'first_name', 'last_name'],
                'serviceType' => ['name'],
            ],
        );

        return $query->orderByDesc('submitted_at')
            ->limit($limit)
            ->get()
            ->map(function (StudentServiceRequest $request) {
                $student = $request->studentProfile;

                return $this->result(
                    id: $request->id,
                    title: $request->request_number,
                    subtitle: $student
                        ? trim($student->first_name.' '.$student->last_name).' · '.$student->student_number
                        : 'Student unavailable',
                    meta: $request->serviceType?->name,
                    url: route('admin.student-service-requests.show', $request),
                    badge: ucfirst(str_replace('_', ' ', (string) $request->status)),
                    badgeTone: $this->statusTone($request->status),
                );
            })
            ->all();
    }

    protected function adminAppointments(string $term, int $limit): array
    {
        $query = GuidanceAppointment::query()->with(['studentProfile', 'counselor', 'appointmentType']);

        $this->applyColumnSearch(
            $query,
            ['appointment_type', 'reason', 'status', 'appointment_date'],
            $term,
            [
                'studentProfile' => ['student_number', 'first_name', 'last_name'],
                'counselor' => ['name', 'email'],
                'appointmentType' => ['name'],
            ],
        );

        return $query->orderByDesc('appointment_date')
            ->orderByDesc('start_time')
            ->limit($limit)
            ->get()
            ->map(fn (GuidanceAppointment $appointment) => $this->result(
                id: $appointment->id,
                title: $appointment->studentProfile
                    ? trim($appointment->studentProfile->first_name.' '.$appointment->studentProfile->last_name)
                    : 'Student unavailable',
                subtitle: collect([
                    $appointment->appointmentType?->name ?? $appointment->appointment_type,
                    $appointment->appointment_date?->format('M d, Y'),
                    $appointment->start_time ? substr((string) $appointment->start_time, 0, 5) : null,
                ])->filter()->implode(' · '),
                meta: $appointment->counselor?->name,
                url: route('admin.guidance.appointments.show', $appointment),
                badge: ucfirst((string) $appointment->status),
                badgeTone: $this->statusTone($appointment->status),
            ))
            ->all();
    }

    protected function adminAuditLogs(string $term, int $limit): array
    {
        return AuditLog::query()
            ->with('user')
            ->search($term)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (AuditLog $log) => $this->result(
                id: $log->id,
                title: Str::limit((string) $log->description, 90),
                subtitle: collect([$log->user?->name ?? 'System', $log->action])->filter()->implode(' · '),
                meta: collect([
                    $log->module,
                    $log->created_at?->format('M d, Y g:i A'),
                ])->filter()->implode(' · '),
                url: route('admin.audit-logs.show', $log),
                badge: $log->record_type ? Str::limit((string) $log->record_type, 24) : null,
                badgeTone: 'neutral',
            ))
            ->all();
    }

    protected function adminAnalytics(string $term, int $limit): array
    {
        $perModule = max(1, (int) ceil($limit / 3));

        return array_slice(array_merge(
            $this->adminApplications($term, $perModule),
            $this->adminAppointments($term, $perModule),
            $this->adminServiceRequests($term, $perModule),
        ), 0, $limit);
    }

    protected function adminGlobal(string $term, int $limit): array
    {
        $perModule = max(1, (int) ceil($limit / 6));

        return array_slice(array_merge(
            $this->adminStudents($term, $perModule),
            $this->adminUsers($term, $perModule),
            $this->adminScholarships($term, $perModule),
            $this->adminApplications($term, $perModule),
            $this->adminServiceRequests($term, $perModule),
            $this->adminAppointments($term, $perModule),
        ), 0, $limit);
    }

    // ── Student modules ─────────────────────────────────────────

    protected function studentScholarships(string $term, ?StudentProfile $profile, int $limit): array
    {
        return $this->scholarshipSearch
            ->quickSearch($term, $profile, $limit)
            ->map(function (Scholarship $scholarship) {
                $match = $scholarship->match_result ?? null;

                return $this->result(
                    id: $scholarship->id,
                    title: $scholarship->title,
                    subtitle: collect([$scholarship->provider, $scholarship->scholarship_type])->filter()->implode(' · '),
                    meta: 'Deadline: '.$scholarship->application_deadline->format('M d, Y'),
                    url: route('student.scholarships.show', $scholarship),
                    badge: $match ? ($match->isEligible() ? 'Eligible' : 'Not eligible') : null,
                    badgeTone: $match ? ($match->isEligible() ? 'success' : 'neutral') : 'neutral',
                );
            })
            ->all();
    }

    protected function studentApplications(string $term, ?StudentProfile $profile, int $limit): array
    {
        if (! $profile) {
            return [];
        }

        $query = ScholarshipApplication::query()
            ->where('student_profile_id', $profile->id)
            ->with('scholarship');

        $this->applyColumnSearch(
            $query,
            ['application_number'],
            $term,
            ['scholarship' => ['title', 'provider']],
        );

        return $query->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (ScholarshipApplication $application) => $this->result(
                id: $application->id,
                title: $application->application_number,
                subtitle: $application->scholarship?->title,
                meta: $application->submitted_at
                    ? 'Submitted '.$application->submitted_at->format('M d, Y')
                    : 'Draft application',
                url: route('student.applications.show', $application),
                badge: ucfirst(str_replace('_', ' ', (string) $application->status)),
                badgeTone: $this->statusTone($application->status),
            ))
            ->all();
    }

    protected function studentServiceRequests(string $term, ?StudentProfile $profile, int $limit): array
    {
        if (! $profile) {
            return [];
        }

        $query = StudentServiceRequest::query()
            ->where('student_profile_id', $profile->id)
            ->with('serviceType');

        $this->applyColumnSearch(
            $query,
            ['request_number', 'description', 'status'],
            $term,
            ['serviceType' => ['name']],
        );

        return $query->orderByDesc('submitted_at')
            ->limit($limit)
            ->get()
            ->map(fn (StudentServiceRequest $request) => $this->result(
                id: $request->id,
                title: $request->request_number,
                subtitle: $request->serviceType?->name,
                meta: $request->submitted_at
                    ? 'Submitted '.$request->submitted_at->format('M d, Y')
                    : null,
                url: route('student.services.show', $request),
                badge: ucfirst(str_replace('_', ' ', (string) $request->status)),
                badgeTone: $this->statusTone($request->status),
            ))
            ->all();
    }

    protected function studentAppointments(string $term, ?StudentProfile $profile, int $limit): array
    {
        if (! $profile) {
            return [];
        }

        $query = GuidanceAppointment::query()
            ->where('student_profile_id', $profile->id)
            ->with(['counselor', 'appointmentType']);

        $this->applyColumnSearch(
            $query,
            ['appointment_type', 'reason', 'status', 'appointment_date'],
            $term,
            [
                'counselor' => ['name'],
                'appointmentType' => ['name'],
            ],
        );

        return $query->orderByDesc('appointment_date')
            ->orderByDesc('start_time')
            ->limit($limit)
            ->get()
            ->map(fn (GuidanceAppointment $appointment) => $this->result(
                id: $appointment->id,
                title: $appointment->appointmentType?->name ?? $appointment->appointment_type,
                subtitle: collect([
                    $appointment->appointment_date?->format('M d, Y'),
                    $appointment->start_time ? substr((string) $appointment->start_time, 0, 5) : null,
                ])->filter()->implode(' · '),
                meta: $appointment->counselor?->name,
                url: route('student.guidance.appointments.show', $appointment),
                badge: ucfirst((string) $appointment->status),
                badgeTone: $this->statusTone($appointment->status),
            ))
            ->all();
    }

    protected function studentServices(string $term, int $limit): array
    {
        $query = ServiceType::query()->where('is_active', true);

        $this->applyColumnSearch($query, ['name', 'description', 'instructions'], $term);

        return $query->orderBy('display_order')
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(fn (ServiceType $service) => $this->result(
                id: $service->id,
                title: $service->name,
                subtitle: Str::limit((string) $service->description, 110),
                meta: 'Request this service',
                url: route('student.services.create', $service),
                badge: 'Service',
                badgeTone: 'info',
            ))
            ->all();
    }

    protected function studentCounselors(string $term, int $limit): array
    {
        $query = User::query()
            ->whereHas('role', fn (Builder $role) => $role->where('name', 'counselor'))
            ->where('is_active', true);

        $this->applyColumnSearch($query, ['name', 'email'], $term);

        return $query->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(fn (User $counselor) => $this->result(
                id: $counselor->id,
                title: $counselor->name,
                subtitle: $counselor->email,
                meta: 'View available appointment slots',
                url: route('student.guidance.slots', $counselor),
                badge: 'Counselor',
                badgeTone: 'info',
            ))
            ->all();
    }

    protected function studentGlobal(string $term, ?StudentProfile $profile, int $limit): array
    {
        $perModule = max(1, (int) ceil($limit / 4));

        return array_slice(array_merge(
            $this->studentScholarships($term, $profile, $perModule),
            $this->studentApplications($term, $profile, $perModule),
            $this->studentServiceRequests($term, $profile, $perModule),
            $this->studentAppointments($term, $profile, $perModule),
        ), 0, $limit);
    }

    // ── Counselor modules ───────────────────────────────────────

    protected function counselorAppointments(string $term, User $counselor, int $limit): array
    {
        $query = GuidanceAppointment::query()
            ->where('counselor_id', $counselor->id)
            ->with(['studentProfile', 'appointmentType']);

        $this->applyColumnSearch(
            $query,
            ['appointment_type', 'reason', 'status', 'appointment_date'],
            $term,
            [
                'studentProfile' => ['student_number', 'first_name', 'last_name'],
                'appointmentType' => ['name'],
            ],
        );

        return $query->orderByDesc('appointment_date')
            ->orderByDesc('start_time')
            ->limit($limit)
            ->get()
            ->map(fn (GuidanceAppointment $appointment) => $this->result(
                id: $appointment->id,
                title: $appointment->studentProfile
                    ? trim($appointment->studentProfile->first_name.' '.$appointment->studentProfile->last_name)
                    : 'Student unavailable',
                subtitle: collect([
                    $appointment->appointmentType?->name ?? $appointment->appointment_type,
                    $appointment->appointment_date?->format('M d, Y'),
                    $appointment->start_time ? substr((string) $appointment->start_time, 0, 5) : null,
                ])->filter()->implode(' · '),
                meta: $appointment->studentProfile?->student_number,
                url: route('counselor.guidance.appointments.show', $appointment),
                badge: ucfirst((string) $appointment->status),
                badgeTone: $this->statusTone($appointment->status),
            ))
            ->all();
    }

    // ── Shared modules ──────────────────────────────────────────

    protected function enrollmentVerifications(string $term, int $limit): array
    {
        $query = StudentEnrollmentVerification::query()->with(['studentProfile.user']);

        $this->applyColumnSearch(
            $query,
            ['academic_year', 'semester', 'status', 'notes'],
            $term,
            [
                'studentProfile' => ['student_number', 'first_name', 'last_name'],
                'studentProfile.user' => ['email', 'name'],
            ],
        );

        return $query->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function (StudentEnrollmentVerification $verification) {
                $profile = $verification->studentProfile;

                return $this->result(
                    id: $verification->id,
                    title: $profile
                        ? trim($profile->first_name.' '.$profile->last_name)
                        : 'Student unavailable',
                    subtitle: collect([
                        $profile?->student_number,
                        $profile?->user?->email,
                    ])->filter()->implode(' · '),
                    meta: collect([
                        $verification->academic_year,
                        $verification->semester ? ucfirst($verification->semester) : null,
                    ])->filter()->implode(' · '),
                    url: route('enrollment-verifications.show', $verification),
                    badge: ucfirst(str_replace('_', ' ', (string) $verification->status)),
                    badgeTone: $this->statusTone($verification->status),
                );
            })
            ->all();
    }

    protected function notifications(string $term, User $user, int $limit): array
    {
        $query = Notification::query()->forUser($user->id);

        $this->applyColumnSearch($query, ['title', 'message', 'type', 'related_type'], $term);

        return $query->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (Notification $notification) => $this->result(
                id: $notification->id,
                title: $notification->title,
                subtitle: Str::limit((string) $notification->message, 120),
                meta: collect([
                    $notification->getCategoryLabel(),
                    $notification->created_at?->format('M d, Y g:i A'),
                ])->filter()->implode(' · '),
                url: $notification->getDetailUrl() ?: route('notifications.index').'#notification-'.$notification->id,
                badge: $notification->isUnread() ? 'Unread' : 'Read',
                badgeTone: $notification->isUnread() ? 'warning' : 'neutral',
            ))
            ->all();
    }

    // ── Query helpers ───────────────────────────────────────────

    /**
     * Match every token against at least one of the supplied columns or
     * supported relationships. This keeps partial, multi-word searches
     * predictable while escaping SQL wildcard characters.
     *
     * @param  array<int, string>  $columns
     * @param  array<string, array<int, string>>  $related
     */
    protected function applyColumnSearch(Builder $query, array $columns, string $term, array $related = []): void
    {
        $tokens = preg_split('/\s+/u', $term, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($tokens === []) {
            return;
        }

        $query->where(function (Builder $outer) use ($tokens, $columns, $related) {
            foreach ($tokens as $token) {
                $like = $this->like($token);

                $outer->where(function (Builder $inner) use ($columns, $like, $related) {
                    foreach ($columns as $column) {
                        $inner->orWhereRaw($this->likeClause($column), [$like]);
                    }

                    foreach ($related as $relation => $relationColumns) {
                        $inner->orWhereHas($relation, function (Builder $relationQuery) use ($relationColumns, $like) {
                            // The extra where() wrapper is required: without it the
                            // OR conditions would be appended to the relation's
                            // foreign-key constraint and match unrelated rows.
                            $relationQuery->where(function (Builder $relationGroup) use ($relationColumns, $like) {
                                foreach ($relationColumns as $column) {
                                    $relationGroup->orWhereRaw($this->likeClause($column), [$like]);
                                }
                            });
                        });
                    }
                });
            }
        });
    }

    protected function like(string $value): string
    {
        // Escape LIKE wildcards with an explicit ESCAPE character so the same
        // behaviour holds on MySQL/MariaDB and SQLite (used by the test suite).
        return '%'.addcslashes($value, '!%_').'%';
    }

    protected function likeClause(string $column): string
    {
        return "{$column} LIKE ? ESCAPE '!'";
    }

    protected function clampLimit(int $limit): int
    {
        return max(1, min($limit, self::MAX_LIMIT));
    }

    /**
     * @return array{id: int, title: string, subtitle: ?string, meta: ?string, url: string, badge: ?string, badge_tone: string}
     */
    protected function result(
        int $id,
        string $title,
        ?string $subtitle = null,
        ?string $meta = null,
        string $url = '#',
        ?string $badge = null,
        string $badgeTone = 'neutral',
    ): array {
        return [
            'id' => $id,
            'title' => $title,
            'subtitle' => filled($subtitle) ? $subtitle : null,
            'meta' => filled($meta) ? $meta : null,
            'url' => $url,
            'badge' => filled($badge) ? $badge : null,
            'badge_tone' => $badgeTone,
        ];
    }

    protected function statusTone(?string $status): string
    {
        return match (strtolower((string) $status)) {
            'approved', 'completed', 'confirmed', 'verified', 'active', 'open', 'enrolled', 'sent' => 'success',
            'rejected', 'cancelled', 'deactivated', 'unable_to_verify', 'not_enrolled', 'closed' => 'danger',
            'requested', 'processing', 'under_review', 'document_verification', 'pending', 'requires_information', 'draft', 'warning' => 'warning',
            'submitted', 'info', 'read' => 'info',
            default => 'neutral',
        };
    }

    protected function enrollmentTone(?string $status): string
    {
        return match ($status) {
            EnrollmentStatus::ENROLLED->value => 'success',
            EnrollmentStatus::NOT_ENROLLED->value => 'danger',
            default => 'neutral',
        };
    }
}
