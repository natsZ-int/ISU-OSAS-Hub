<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Enums\EnrollmentVerificationStatus;
use App\Models\AuditLog;
use App\Models\CounselorAvailability;
use App\Models\GuidanceAppointment;
use App\Models\GuidanceAppointmentType;
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

class QuickSearchService
{
    public const MAX_TERM_LENGTH = 100;

    public const MAX_RESULTS = 8;

    /**
     * Whether the given module exists in the server-side registry.
     */
    public function hasModule(string $module): bool
    {
        return array_key_exists($module, $this->modules());
    }

    /**
     * Run a scoped suggestion search.
     *
     * Every module declares the roles allowed to use it. Record-level scoping
     * (own applications, own notifications, one counselor's appointments) is
     * applied inside the module resolver so no unauthorized row can leak.
     *
     * @return array{query: string, count: int, results: array<int, array<string, mixed>>}
     */
    public function search(string $module, ?string $term, User $user): array
    {
        $modules = $this->modules();

        abort_unless(isset($modules[$module]), 404);

        $config = $modules[$module];

        abort_unless(
            $user->isActive() && in_array($user->role?->name, $config['roles'], true),
            403
        );

        $term = $this->normalizeTerm($term);

        if ($term === null) {
            return ['query' => '', 'count' => 0, 'results' => []];
        }

        $escaped = addcslashes($term, '%_\\');
        $contains = '%'.$escaped.'%';
        $prefix = $escaped.'%';

        $results = collect(($config['resolver'])($term, $contains, $prefix, $user))
            ->filter()
            ->take(self::MAX_RESULTS)
            ->values();

        return [
            'query' => $term,
            'count' => $results->count(),
            'results' => $results->all(),
        ];
    }

    /**
     * Normalise and cap user input before it reaches any query.
     */
    protected function normalizeTerm(?string $term): ?string
    {
        if (! is_string($term)) {
            return null;
        }

        $trimmed = trim($term);

        if ($trimmed === '') {
            return null;
        }

        $collapsed = preg_replace('/\s+/u', ' ', $trimmed) ?? $trimmed;

        return Str::limit($collapsed, self::MAX_TERM_LENGTH, '');
    }

    /**
     * Add a token-based, partial-match filter across local columns and
     * related-model columns. Every entered word must match at least one field.
     *
     * @param  array<int, string>  $columns
     * @param  array<string, array<int, string>>  $relations
     */
    protected function applyKeywordSearch(Builder $query, string $term, array $columns = [], array $relations = []): void
    {
        $tokens = preg_split('/\s+/u', $term, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($tokens as $token) {
            $like = '%'.addcslashes($token, '%_\\').'%';

            $query->where(function (Builder $outer) use ($columns, $relations, $like) {
                $outer->where(function (Builder $any) use ($columns, $relations, $like) {
                    $first = true;

                    foreach ($columns as $column) {
                        if ($first) {
                            $any->where($column, 'like', $like);
                            $first = false;
                        } else {
                            $any->orWhere($column, 'like', $like);
                        }
                    }

                    foreach ($relations as $relation => $relationColumns) {
                        $callback = function (Builder $related) use ($relationColumns, $like) {
                            $relationFirst = true;

                            foreach ($relationColumns as $column) {
                                if ($relationFirst) {
                                    $related->where($column, 'like', $like);
                                    $relationFirst = false;
                                } else {
                                    $related->orWhere($column, 'like', $like);
                                }
                            }
                        };

                        if ($first) {
                            $any->whereHas($relation, $callback);
                            $first = false;
                        } else {
                            $any->orWhereHas($relation, $callback);
                        }
                    }

                    // A term containing only unsupported characters still gets
                    // a deterministic, non-leaking empty result.
                    if ($first) {
                        $any->whereRaw('1 = 0');
                    }
                });
            });
        }
    }

    protected function initials(?string $text): string
    {
        $words = preg_split('/\s+/u', trim((string) $text), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return Str::upper(collect($words)->take(2)->map(fn ($word) => Str::substr($word, 0, 1))->implode(''));
    }

    /**
     * The complete searchable-module registry.
     *
     * Adding a new searchable page means adding one resolver here and wiring
     * its search box to the shared endpoint.
     *
     * @return array<string, array{roles: array<int, string>, resolver: callable}>
     */
    protected function modules(): array
    {
        return [
            'admin-scholarships' => [
                'roles' => ['admin'],
                'resolver' => function (string $term, string $contains, string $prefix): array {
                    $query = Scholarship::query()->withCount('applications');

                    $this->applyKeywordSearch($query, $term, [
                        'title', 'provider', 'scholarship_type', 'description',
                    ]);

                    return $query
                        ->orderByRaw(
                            'CASE WHEN title LIKE ? THEN 0 WHEN provider LIKE ? THEN 1 ELSE 2 END',
                            [$prefix, $prefix]
                        )
                        ->orderBy('title')
                        ->limit(self::MAX_RESULTS)
                        ->get()
                        ->map(fn (Scholarship $scholarship) => [
                            'id' => $scholarship->id,
                            'title' => $scholarship->title,
                            'subtitle' => $scholarship->provider ?: 'No provider',
                            'meta' => trim(implode(' · ', array_filter([
                                $scholarship->scholarship_type,
                                $scholarship->application_deadline
                                    ? 'Deadline '.$scholarship->application_deadline->format('M d, Y')
                                    : null,
                                $scholarship->applications_count.' application(s)',
                            ]))),
                            'badge' => ucfirst($scholarship->status),
                            'badge_tone' => $scholarship->status,
                            'search' => $scholarship->title,
                            'avatar' => $this->initials($scholarship->title),
                            'url' => route('admin.scholarships.edit', $scholarship),
                        ])
                        ->all();
                },
            ],

            'admin-applications' => [
                'roles' => ['admin'],
                'resolver' => function (string $term): array {
                    $query = ScholarshipApplication::query()
                        ->with(['studentProfile.user', 'scholarship']);

                    $this->applyKeywordSearch($query, $term, [
                        'application_number', 'status',
                    ], [
                        'studentProfile' => ['first_name', 'last_name', 'student_number'],
                        'studentProfile.user' => ['name', 'email'],
                        'scholarship' => ['title', 'provider'],
                    ]);

                    return $query
                        ->orderByDesc('submitted_at')
                        ->orderByDesc('id')
                        ->limit(self::MAX_RESULTS)
                        ->get()
                        ->map(function (ScholarshipApplication $application) {
                            $student = $application->studentProfile;
                            $studentName = $student
                                ? trim($student->first_name.' '.$student->last_name)
                                : 'Unknown student';

                            return [
                                'id' => $application->id,
                                'title' => $application->application_number.' — '.$studentName,
                                'subtitle' => $application->scholarship?->title ?? 'Scholarship unavailable',
                                'meta' => trim(implode(' · ', array_filter([
                                    $student?->student_number
                                        ? 'Student ID '.$student->student_number
                                        : null,
                                    $application->status
                                        ? 'Status '.ucfirst(str_replace('_', ' ', $application->status))
                                        : null,
                                    $application->submitted_at
                                        ? 'Submitted '.$application->submitted_at->format('M d, Y')
                                        : 'Draft',
                                ]))),
                                'badge' => $application->status
                                    ? ucfirst(str_replace('_', ' ', $application->status))
                                    : null,
                                'badge_tone' => $application->status,
                                'search' => $application->application_number,
                                'avatar' => $this->initials($studentName),
                                'url' => route('admin.scholarship-applications.show', $application),
                            ];
                        })
                        ->all();
                },
            ],

            'admin-students' => [
                'roles' => ['admin'],
                'resolver' => function (string $term): array {
                    $query = StudentProfile::query()->with('user');

                    $this->applyKeywordSearch($query, $term, [
                        'student_number', 'first_name', 'last_name', 'middle_name',
                        'program', 'college',
                    ], [
                        'user' => ['name', 'email'],
                    ]);

                    return $query
                        ->orderBy('last_name')
                        ->orderBy('first_name')
                        ->limit(self::MAX_RESULTS)
                        ->get()
                        ->map(function (StudentProfile $student) {
                            $name = trim($student->first_name.' '.$student->last_name);
                            $enrollment = EnrollmentStatus::tryFrom($student->enrollment_status ?? 'unknown');

                            return [
                                'id' => $student->id,
                                'title' => $name !== '' ? $name : 'Unnamed student',
                                'subtitle' => $student->student_number.' — '.($student->program ?: 'No program'),
                                'meta' => trim(implode(' · ', array_filter([
                                    $student->user?->email,
                                    $student->college,
                                    $student->year_level ? 'Year '.$student->year_level : null,
                                ]))),
                                'badge' => $enrollment?->label() ?? 'Unknown',
                                'badge_tone' => $enrollment?->value ?? 'unknown',
                                'search' => $name,
                                'avatar' => $this->initials($name),
                                'url' => route('admin.student-profiles.show', $student),
                            ];
                        })
                        ->all();
                },
            ],

            'admin-users' => [
                'roles' => ['admin'],
                'resolver' => function (string $term): array {
                    $query = User::query()->with(['role', 'studentProfile']);

                    $this->applyKeywordSearch($query, $term, [
                        'name', 'email',
                    ], [
                        'role' => ['name', 'label'],
                        'studentProfile' => ['student_number', 'first_name', 'last_name'],
                    ]);

                    return $query
                        ->orderBy('name')
                        ->limit(self::MAX_RESULTS)
                        ->get()
                        ->map(fn (User $user) => [
                            'id' => $user->id,
                            'title' => $user->name,
                            'subtitle' => $user->email,
                            'meta' => trim(implode(' · ', array_filter([
                                $user->studentProfile?->student_number
                                    ? 'Student ID '.$user->studentProfile->student_number
                                    : null,
                                $user->is_active ? 'Active account' : 'Inactive account',
                            ]))),
                            'badge' => $user->role?->label ?? ucfirst($user->role?->name ?? 'N/A'),
                            'badge_tone' => $user->role?->name,
                            'search' => $user->name,
                            'avatar' => $this->initials($user->name),
                            'url' => route('admin.users.edit', $user),
                        ])
                        ->all();
                },
            ],

            'admin-service-requests' => [
                'roles' => ['admin'],
                'resolver' => function (string $term): array {
                    $query = StudentServiceRequest::query()
                        ->with(['studentProfile.user', 'serviceType', 'handler']);

                    $this->applyKeywordSearch($query, $term, [
                        'request_number', 'status', 'description',
                    ], [
                        'studentProfile' => ['first_name', 'last_name', 'student_number'],
                        'serviceType' => ['name', 'description'],
                        'handler' => ['name', 'email'],
                    ]);

                    return $query
                        ->orderByDesc('submitted_at')
                        ->limit(self::MAX_RESULTS)
                        ->get()
                        ->map(function (StudentServiceRequest $serviceRequest) {
                            $student = $serviceRequest->studentProfile;
                            $studentName = $student
                                ? trim($student->first_name.' '.$student->last_name)
                                : 'Unknown student';

                            return [
                                'id' => $serviceRequest->id,
                                'title' => $serviceRequest->request_number.' — '.($serviceRequest->serviceType?->name ?? 'Service request'),
                                'subtitle' => $studentName.($student?->student_number ? ' · '.$student->student_number : ''),
                                'meta' => trim(implode(' · ', array_filter([
                                    'Status '.ucfirst(str_replace('_', ' ', $serviceRequest->status)),
                                    $serviceRequest->handler?->name
                                        ? 'Handler '.$serviceRequest->handler->name
                                        : 'Unassigned',
                                    $serviceRequest->submitted_at
                                        ? 'Submitted '.$serviceRequest->submitted_at->format('M d, Y')
                                        : null,
                                ]))),
                                'badge' => ucfirst(str_replace('_', ' ', $serviceRequest->status)),
                                'badge_tone' => $serviceRequest->status,
                                'search' => $serviceRequest->request_number,
                                'avatar' => $this->initials($studentName),
                                'url' => route('admin.student-service-requests.show', $serviceRequest),
                            ];
                        })
                        ->all();
                },
            ],

            'admin-service-types' => [
                'roles' => ['admin'],
                'resolver' => function (string $term): array {
                    $query = ServiceType::query()->withCount('serviceRequests');

                    $this->applyKeywordSearch($query, $term, [
                        'name', 'description', 'instructions',
                    ]);

                    return $query
                        ->orderBy('display_order')
                        ->orderBy('name')
                        ->limit(self::MAX_RESULTS)
                        ->get()
                        ->map(fn (ServiceType $type) => [
                            'id' => $type->id,
                            'title' => $type->name,
                            'subtitle' => $type->description ?: 'No description',
                            'meta' => trim(implode(' · ', array_filter([
                                $type->service_requests_count.' request(s)',
                                $type->is_active ? 'Available to students' : 'Disabled',
                                'Display order '.$type->display_order,
                            ]))),
                            'badge' => $type->is_active ? 'Active' : 'Disabled',
                            'badge_tone' => $type->is_active ? 'active' : 'inactive',
                            'search' => $type->name,
                            'avatar' => $this->initials($type->name),
                            'url' => route('admin.service-types.edit', $type),
                        ])
                        ->all();
                },
            ],

            'admin-guidance-appointments' => [
                'roles' => ['admin'],
                'resolver' => function (string $term): array {
                    $query = GuidanceAppointment::query()
                        ->with(['studentProfile', 'counselor', 'appointmentType']);

                    $this->applyKeywordSearch($query, $term, [
                        'status', 'appointment_type', 'reason',
                        'appointment_date', 'start_time', 'end_time',
                    ], [
                        'studentProfile' => ['first_name', 'last_name', 'student_number'],
                        'counselor' => ['name', 'email'],
                        'appointmentType' => ['name', 'description'],
                    ]);

                    return $query
                        ->orderByDesc('appointment_date')
                        ->orderBy('start_time')
                        ->limit(self::MAX_RESULTS)
                        ->get()
                        ->map(function (GuidanceAppointment $appointment) {
                            $student = $appointment->studentProfile;
                            $studentName = $student
                                ? trim($student->first_name.' '.$student->last_name)
                                : 'Unknown student';
                            $type = $appointment->appointmentType?->name
                                ?? $appointment->appointment_type
                                ?? 'Guidance appointment';

                            return [
                                'id' => $appointment->id,
                                'title' => $studentName.' — '.$type,
                                'subtitle' => $appointment->appointment_date?->format('M d, Y')
                                    .' · '.substr((string) $appointment->start_time, 0, 5)
                                    .'–'.substr((string) $appointment->end_time, 0, 5),
                                'meta' => trim(implode(' · ', array_filter([
                                    $appointment->counselor?->name
                                        ? 'Counselor '.$appointment->counselor->name
                                        : null,
                                    $student?->student_number
                                        ? 'Student ID '.$student->student_number
                                        : null,
                                    $appointment->reason,
                                ]))),
                                'badge' => ucfirst($appointment->status),
                                'badge_tone' => $appointment->status,
                                'search' => $studentName,
                                'avatar' => $this->initials($studentName),
                                'url' => route('admin.guidance.appointments', [
                                    'search' => $student?->student_number ?: $studentName,
                                ]).'#appointment-'.$appointment->id,
                            ];
                        })
                        ->all();
                },
            ],

            'admin-guidance-types' => [
                'roles' => ['admin'],
                'resolver' => function (string $term): array {
                    $query = GuidanceAppointmentType::query()->withCount('appointments');

                    $this->applyKeywordSearch($query, $term, ['name', 'description']);

                    return $query
                        ->orderBy('display_order')
                        ->orderBy('name')
                        ->limit(self::MAX_RESULTS)
                        ->get()
                        ->map(fn (GuidanceAppointmentType $type) => [
                            'id' => $type->id,
                            'title' => $type->name,
                            'subtitle' => $type->description ?: 'No description',
                            'meta' => trim(implode(' · ', array_filter([
                                $type->duration_minutes
                                    ? $type->duration_minutes.' minutes'
                                    : 'Default duration',
                                $type->appointments_count.' appointment(s)',
                            ]))),
                            'badge' => $type->is_active ? 'Active' : 'Inactive',
                            'badge_tone' => $type->is_active ? 'active' : 'inactive',
                            'search' => $type->name,
                            'avatar' => $this->initials($type->name),
                            'url' => route('admin.guidance.types').'#type-'.$type->id,
                        ])
                        ->all();
                },
            ],

            'admin-audit-logs' => [
                'roles' => ['admin'],
                'resolver' => function (string $term): array {
                    $query = AuditLog::query()->with('user');

                    $this->applyKeywordSearch($query, $term, [
                        'description', 'action', 'module', 'record_type',
                        'record_id', 'ip_address',
                    ], [
                        'user' => ['name', 'email'],
                    ]);

                    return $query
                        ->orderByDesc('created_at')
                        ->orderByDesc('id')
                        ->limit(self::MAX_RESULTS)
                        ->get()
                        ->map(fn (AuditLog $log) => [
                            'id' => $log->id,
                            'title' => $log->action,
                            'subtitle' => Str::limit((string) $log->description, 110, '…'),
                            'meta' => trim(implode(' · ', array_filter([
                                $log->user?->name ?? 'System',
                                $log->module,
                                $log->created_at?->format('M d, Y g:i A'),
                            ]))),
                            'badge' => $log->module,
                            'badge_tone' => 'info',
                            'search' => $log->action,
                            'avatar' => $this->initials($log->action),
                            'url' => route('admin.audit-logs.show', $log),
                        ])
                        ->all();
                },
            ],

            'enrollment-verifications' => [
                'roles' => ['admin', 'counselor'],
                'resolver' => function (string $term): array {
                    $query = StudentEnrollmentVerification::query()
                        ->with(['studentProfile.user']);

                    $this->applyKeywordSearch($query, $term, [
                        'academic_year', 'semester', 'status', 'notes',
                    ], [
                        'studentProfile' => ['first_name', 'last_name', 'student_number', 'program'],
                        'studentProfile.user' => ['name', 'email'],
                    ]);

                    return $query
                        ->orderByDesc('created_at')
                        ->limit(self::MAX_RESULTS)
                        ->get()
                        ->map(function (StudentEnrollmentVerification $verification) {
                            $student = $verification->studentProfile;
                            $studentName = $student
                                ? trim($student->first_name.' '.$student->last_name)
                                : 'Unknown student';
                            $status = EnrollmentVerificationStatus::tryFrom($verification->status);

                            return [
                                'id' => $verification->id,
                                'title' => $studentName,
                                'subtitle' => $verification->academic_year
                                    .' · '.ucfirst((string) $verification->semester)
                                    .($student?->student_number ? ' · '.$student->student_number : ''),
                                'meta' => trim(implode(' · ', array_filter([
                                    $student?->user?->email,
                                    $status?->label() ?? ucfirst((string) $verification->status),
                                ]))),
                                'badge' => $status?->label() ?? ucfirst((string) $verification->status),
                                'badge_tone' => $verification->status,
                                'search' => $studentName,
                                'avatar' => $this->initials($studentName),
                                'url' => route('enrollment-verifications.show', $verification),
                            ];
                        })
                        ->all();
                },
            ],

            'counselor-guidance-appointments' => [
                'roles' => ['counselor'],
                'resolver' => function (string $term, string $contains, string $prefix, User $user): array {
                    $query = GuidanceAppointment::query()
                        ->where('counselor_id', $user->id)
                        ->with(['studentProfile', 'appointmentType']);

                    $this->applyKeywordSearch($query, $term, [
                        'status', 'appointment_type', 'reason',
                        'appointment_date', 'start_time', 'end_time',
                    ], [
                        'studentProfile' => ['first_name', 'last_name', 'student_number'],
                        'appointmentType' => ['name', 'description'],
                    ]);

                    return $query
                        ->orderByDesc('appointment_date')
                        ->orderBy('start_time')
                        ->limit(self::MAX_RESULTS)
                        ->get()
                        ->map(function (GuidanceAppointment $appointment) {
                            $student = $appointment->studentProfile;
                            $studentName = $student
                                ? trim($student->first_name.' '.$student->last_name)
                                : 'Unknown student';
                            $type = $appointment->appointmentType?->name
                                ?? $appointment->appointment_type
                                ?? 'Guidance appointment';

                            return [
                                'id' => $appointment->id,
                                'title' => $studentName.' — '.$type,
                                'subtitle' => $appointment->appointment_date?->format('M d, Y')
                                    .' · '.substr((string) $appointment->start_time, 0, 5)
                                    .'–'.substr((string) $appointment->end_time, 0, 5),
                                'meta' => trim(implode(' · ', array_filter([
                                    $student?->student_number
                                        ? 'Student ID '.$student->student_number
                                        : null,
                                    'Status '.ucfirst((string) $appointment->status),
                                ]))),
                                'badge' => ucfirst($appointment->status),
                                'badge_tone' => $appointment->status,
                                'search' => $studentName,
                                'avatar' => $this->initials($studentName),
                                'url' => route('counselor.guidance.appointments.show', $appointment),
                            ];
                        })
                        ->all();
                },
            ],

            'student-applications' => [
                'roles' => ['student'],
                'resolver' => function (string $term, string $contains, string $prefix, User $user): array {
                    $profile = $user->studentProfile;

                    if (! $profile) {
                        return [];
                    }

                    $query = ScholarshipApplication::query()
                        ->where('student_profile_id', $profile->id)
                        ->with('scholarship');

                    $this->applyKeywordSearch($query, $term, [
                        'application_number', 'status',
                    ], [
                        'scholarship' => ['title', 'provider', 'scholarship_type'],
                    ]);

                    return $query
                        ->orderByDesc('created_at')
                        ->limit(self::MAX_RESULTS)
                        ->get()
                        ->map(fn (ScholarshipApplication $application) => [
                            'id' => $application->id,
                            'title' => $application->application_number.' — '.($application->scholarship?->title ?? 'Scholarship'),
                            'subtitle' => $application->scholarship?->provider ?? 'Scholarship application',
                            'meta' => trim(implode(' · ', array_filter([
                                'Status '.ucfirst(str_replace('_', ' ', (string) $application->status)),
                                $application->submitted_at
                                    ? 'Submitted '.$application->submitted_at->format('M d, Y')
                                    : 'Draft',
                            ]))),
                            'badge' => ucfirst(str_replace('_', ' ', (string) $application->status)),
                            'badge_tone' => $application->status,
                            'search' => $application->application_number,
                            'avatar' => $this->initials($application->scholarship?->title ?? 'App'),
                            'url' => route('student.applications.show', $application),
                        ])
                        ->all();
                },
            ],

            'student-service-requests' => [
                'roles' => ['student'],
                'resolver' => function (string $term, string $contains, string $prefix, User $user): array {
                    $profile = $user->studentProfile;

                    if (! $profile) {
                        return [];
                    }

                    $query = StudentServiceRequest::query()
                        ->where('student_profile_id', $profile->id)
                        ->with('serviceType');

                    $this->applyKeywordSearch($query, $term, [
                        'request_number', 'status', 'description',
                    ], [
                        'serviceType' => ['name', 'description'],
                    ]);

                    return $query
                        ->orderByDesc('submitted_at')
                        ->limit(self::MAX_RESULTS)
                        ->get()
                        ->map(fn (StudentServiceRequest $serviceRequest) => [
                            'id' => $serviceRequest->id,
                            'title' => $serviceRequest->request_number.' — '.($serviceRequest->serviceType?->name ?? 'Service request'),
                            'subtitle' => 'Status '.ucfirst(str_replace('_', ' ', (string) $serviceRequest->status)),
                            'meta' => trim(implode(' · ', array_filter([
                                $serviceRequest->submitted_at
                                    ? 'Submitted '.$serviceRequest->submitted_at->format('M d, Y')
                                    : null,
                                Str::limit((string) $serviceRequest->description, 70, '…'),
                            ]))),
                            'badge' => ucfirst(str_replace('_', ' ', (string) $serviceRequest->status)),
                            'badge_tone' => $serviceRequest->status,
                            'search' => $serviceRequest->request_number,
                            'avatar' => $this->initials($serviceRequest->serviceType?->name ?? 'SR'),
                            'url' => route('student.services.show', $serviceRequest),
                        ])
                        ->all();
                },
            ],

            'student-service-catalog' => [
                'roles' => ['student'],
                'resolver' => function (string $term): array {
                    $query = ServiceType::query()
                        ->where('is_active', true)
                        ->withCount('requirements');

                    $this->applyKeywordSearch($query, $term, [
                        'name', 'description', 'instructions',
                    ]);

                    return $query
                        ->orderBy('display_order')
                        ->orderBy('name')
                        ->limit(self::MAX_RESULTS)
                        ->get()
                        ->map(fn (ServiceType $type) => [
                            'id' => $type->id,
                            'title' => $type->name,
                            'subtitle' => $type->description ?: 'Student service',
                            'meta' => $type->requirements_count
                                ? $type->requirements_count.' requirement(s)'
                                : 'No documentary requirements',
                            'badge' => 'Available',
                            'badge_tone' => 'active',
                            'search' => $type->name,
                            'avatar' => $this->initials($type->name),
                            'url' => route('student.services.create', $type),
                        ])
                        ->all();
                },
            ],

            'student-counselors' => [
                'roles' => ['student'],
                'resolver' => function (string $term): array {
                    $query = User::query()
                        ->whereHas('role', fn (Builder $role) => $role->where('name', 'counselor'))
                        ->where('is_active', true)
                        ->with('availabilities');

                    $this->applyKeywordSearch($query, $term, [
                        'name', 'email',
                    ]);

                    return $query
                        ->orderBy('name')
                        ->limit(self::MAX_RESULTS)
                        ->get()
                        ->map(function (User $counselor) {
                            $dayNames = $counselor->availabilities
                                ->where('is_available', true)
                                ->pluck('day_of_week')
                                ->unique()
                                ->sort()
                                ->map(fn ($day) => CounselorAvailability::DAYS[(int) $day] ?? null)
                                ->filter()
                                ->values();

                            return [
                                'id' => $counselor->id,
                                'title' => $counselor->name,
                                'subtitle' => 'Guidance counselor',
                                'meta' => $dayNames->isNotEmpty()
                                    ? 'Available: '.$dayNames->implode(', ')
                                    : 'No published availability',
                                'badge' => 'Counselor',
                                'badge_tone' => 'counselor',
                                'search' => $counselor->name,
                                'avatar' => $this->initials($counselor->name),
                                'url' => route('student.guidance.slots', $counselor),
                            ];
                        })
                        ->all();
                },
            ],

            'student-guidance-appointments' => [
                'roles' => ['student'],
                'resolver' => function (string $term, string $contains, string $prefix, User $user): array {
                    $profile = $user->studentProfile;

                    if (! $profile) {
                        return [];
                    }

                    $query = GuidanceAppointment::query()
                        ->where('student_profile_id', $profile->id)
                        ->with(['counselor', 'appointmentType']);

                    $this->applyKeywordSearch($query, $term, [
                        'status', 'appointment_type', 'reason',
                        'appointment_date', 'start_time', 'end_time',
                    ], [
                        'counselor' => ['name', 'email'],
                        'appointmentType' => ['name', 'description'],
                    ]);

                    return $query
                        ->orderByDesc('appointment_date')
                        ->orderBy('start_time')
                        ->limit(self::MAX_RESULTS)
                        ->get()
                        ->map(function (GuidanceAppointment $appointment) {
                            $type = $appointment->appointmentType?->name
                                ?? $appointment->appointment_type
                                ?? 'Guidance appointment';

                            return [
                                'id' => $appointment->id,
                                'title' => $type.' — '.$appointment->appointment_date?->format('M d, Y'),
                                'subtitle' => substr((string) $appointment->start_time, 0, 5)
                                    .'–'.substr((string) $appointment->end_time, 0, 5)
                                    .' · '.($appointment->counselor?->name ?? 'Counselor'),
                                'meta' => trim(implode(' · ', array_filter([
                                    'Status '.ucfirst((string) $appointment->status),
                                    $appointment->reason,
                                ]))),
                                'badge' => ucfirst($appointment->status),
                                'badge_tone' => $appointment->status,
                                'search' => $type,
                                'avatar' => $this->initials($type),
                                'url' => route('student.guidance.appointments.show', $appointment),
                            ];
                        })
                        ->all();
                },
            ],

            'notifications' => [
                'roles' => ['admin', 'counselor', 'student'],
                'resolver' => function (string $term, string $contains, string $prefix, User $user): array {
                    $query = Notification::query()->forUser($user->id);

                    $this->applyKeywordSearch($query, $term, [
                        'title', 'message', 'type', 'related_type',
                    ]);

                    return $query
                        ->latest('created_at')
                        ->limit(self::MAX_RESULTS)
                        ->get()
                        ->map(fn (Notification $notification) => [
                            'id' => $notification->id,
                            'title' => $notification->title,
                            'subtitle' => Str::limit((string) $notification->message, 110, '…'),
                            'meta' => trim(implode(' · ', array_filter([
                                $notification->getCategoryLabel(),
                                $notification->isUnread() ? 'Unread' : 'Read',
                                $notification->created_at?->format('M d, Y g:i A'),
                            ]))),
                            'badge' => $notification->isUnread() ? 'Unread' : 'Read',
                            'badge_tone' => $notification->isUnread() ? 'info' : 'inactive',
                            'search' => $notification->title,
                            'avatar' => $this->initials($notification->title),
                            'url' => route('notifications.index', [
                                'search' => Str::limit((string) $notification->title, self::MAX_TERM_LENGTH, ''),
                            ]).'#notification-'.$notification->id,
                        ])
                        ->all();
                },
            ],
        ];
    }
}
