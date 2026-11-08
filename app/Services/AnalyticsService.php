<?php

namespace App\Services;

use App\Enums\ScholarshipApplicationStatus;
use App\Models\GuidanceAppointment;
use App\Models\GuidanceAppointmentType;
use App\Models\Scholarship;
use App\Models\ScholarshipApplication;
use App\Models\ServiceType;
use App\Models\StudentServiceRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    // ── Status Groupings ──────────────────────────────────────

    protected const PENDING_APP_STATUSES = [
        ScholarshipApplicationStatus::SUBMITTED->value,
        ScholarshipApplicationStatus::UNDER_REVIEW->value,
        ScholarshipApplicationStatus::DOCUMENT_VERIFICATION->value,
    ];

    protected const DECIDED_APP_STATUSES = [
        ScholarshipApplicationStatus::APPROVED->value,
        ScholarshipApplicationStatus::REJECTED->value,
    ];

    protected const PENDING_SERVICE_STATUSES = [
        StudentServiceRequest::STATUS_SUBMITTED,
        StudentServiceRequest::STATUS_PROCESSING,
        StudentServiceRequest::STATUS_REQUIRES_INFORMATION,
    ];

    // ── Filter Validation ─────────────────────────────────────

    public function validateFilters(array $raw): array
    {
        $filters = [];

        if (! empty($raw['date_from']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw['date_from'])) {
            $filters['date_from'] = $raw['date_from'];
        }
        if (! empty($raw['date_to']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw['date_to'])) {
            $filters['date_to'] = $raw['date_to'];
        }
        if (isset($filters['date_from'], $filters['date_to']) && $filters['date_from'] > $filters['date_to']) {
            unset($filters['date_from'], $filters['date_to']);
        }

        if (! empty($raw['scholarship_id']) && Scholarship::find($raw['scholarship_id'])) {
            $filters['scholarship_id'] = (int) $raw['scholarship_id'];
        }
        if (! empty($raw['service_type_id']) && ServiceType::find($raw['service_type_id'])) {
            $filters['service_type_id'] = (int) $raw['service_type_id'];
        }
        if (! empty($raw['appointment_type_id']) && GuidanceAppointmentType::find($raw['appointment_type_id'])) {
            $filters['appointment_type_id'] = (int) $raw['appointment_type_id'];
        }

        return $filters;
    }

    // ── Filter Helpers ────────────────────────────────────────

    protected function applyDateFilter(Builder $query, string $dateField, array $filters): Builder
    {
        if (! empty($filters['date_from'])) {
            $query->whereDate($dateField, '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate($dateField, '<=', $filters['date_to']);
        }

        return $query;
    }

    protected function baseAppQuery(array $filters): Builder
    {
        $q = ScholarshipApplication::query()
            ->where('scholarship_applications.status', '!=', ScholarshipApplication::STATUS_DRAFT);
        $this->applyDateFilter($q, 'scholarship_applications.submitted_at', $filters);
        if (! empty($filters['scholarship_id'])) {
            $q->where('scholarship_applications.scholarship_id', $filters['scholarship_id']);
        }

        return $q;
    }

    protected function baseApptQuery(array $filters): Builder
    {
        $q = GuidanceAppointment::query();
        $this->applyDateFilter($q, 'guidance_appointments.appointment_date', $filters);
        if (! empty($filters['appointment_type_id'])) {
            $q->where('guidance_appointments.appointment_type_id', $filters['appointment_type_id']);
        }

        return $q;
    }

    protected function baseSvcQuery(array $filters): Builder
    {
        $q = StudentServiceRequest::query();
        $this->applyDateFilter($q, 'student_service_requests.submitted_at', $filters);
        if (! empty($filters['service_type_id'])) {
            $q->where('student_service_requests.service_type_id', $filters['service_type_id']);
        }

        return $q;
    }

    // ── Main Entry Point ──────────────────────────────────────

    public function getAllAnalytics(array $filters): array
    {
        // Status counts are identical for the summary cards and the
        // per-domain breakdowns: compute each grouped query only once.
        [$appCounts, $apptCounts, $svcCounts] = $this->statusCountBundle($filters);

        return [
            'filters' => $filters,
            'summary' => $this->getSummaryCards($filters, $appCounts, $apptCounts, $svcCounts),
            'scholarships' => $this->getScholarshipAnalytics($filters, $appCounts),
            'guidance' => $this->getGuidanceAnalytics($filters, $apptCounts),
            'services' => $this->getServiceAnalytics($filters, $svcCounts),
        ];
    }

    /**
     * Run the three grouped status queries for one filter set.
     *
     * @return array{0: array<string, int>, 1: array<string, int>, 2: array<string, int>}
     *                                                                                    [applications, appointments, service requests]
     */
    protected function statusCountBundle(array $filters): array
    {
        return [
            $this->statusCounts($this->baseAppQuery($filters)),
            $this->statusCounts($this->baseApptQuery($filters)),
            $this->statusCounts($this->baseSvcQuery($filters)),
        ];
    }

    // ── Summary Cards ─────────────────────────────────────────

    /**
     * @param  array<string, int>|null  $appCounts  Pre-computed application status counts
     * @param  array<string, int>|null  $apptCounts  Pre-computed appointment status counts
     * @param  array<string, int>|null  $svcCounts  Pre-computed service request status counts
     */
    public function getSummaryCards(
        array $filters,
        ?array $appCounts = null,
        ?array $apptCounts = null,
        ?array $svcCounts = null
    ): array {
        // One GROUP BY query per base table replaces the previous
        // four separate COUNT(*) queries per table.
        $appCounts ??= $this->statusCounts($this->baseAppQuery($filters));
        $totalApps = array_sum($appCounts);
        $approvedApps = $appCounts[ScholarshipApplicationStatus::APPROVED->value] ?? 0;
        $rejectedApps = $appCounts[ScholarshipApplicationStatus::REJECTED->value] ?? 0;
        $pendingApps = $this->countStatuses($appCounts, self::PENDING_APP_STATUSES);

        $apptCounts ??= $this->statusCounts($this->baseApptQuery($filters));
        $totalAppts = array_sum($apptCounts);
        $pendingAppts = $apptCounts[GuidanceAppointment::STATUS_REQUESTED] ?? 0;
        $completedAppts = $apptCounts[GuidanceAppointment::STATUS_COMPLETED] ?? 0;
        $cancelledAppts = $apptCounts[GuidanceAppointment::STATUS_CANCELLED] ?? 0;

        $svcCounts ??= $this->statusCounts($this->baseSvcQuery($filters));
        $totalSvc = array_sum($svcCounts);
        $processingSvc = $svcCounts[StudentServiceRequest::STATUS_PROCESSING] ?? 0;
        $completedSvc = $svcCounts[StudentServiceRequest::STATUS_COMPLETED] ?? 0;
        $pendingSvc = $this->countStatuses($svcCounts, self::PENDING_SERVICE_STATUSES);

        $decided = $approvedApps + $rejectedApps;

        return [
            'total_applications' => $totalApps,
            'pending_applications' => $pendingApps,
            'approved_applications' => $approvedApps,
            'rejected_applications' => $rejectedApps,
            'approval_rate' => $decided > 0 ? round(($approvedApps / $decided) * 100, 1) : null,
            'rejection_rate' => $decided > 0 ? round(($rejectedApps / $decided) * 100, 1) : null,
            'total_appointments' => $totalAppts,
            'pending_appointments' => $pendingAppts,
            'completed_appointments' => $completedAppts,
            'cancelled_appointments' => $cancelledAppts,
            'total_service_requests' => $totalSvc,
            'pending_services' => $pendingSvc,
            'processing_services' => $processingSvc,
            'completed_services' => $completedSvc,
        ];
    }

    /**
     * Count every status of a query in a single GROUP BY query.
     *
     * @return array<string, int> Counts keyed by raw status value.
     */
    protected function statusCounts(Builder $query): array
    {
        return $query
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($total) => (int) $total)
            ->all();
    }

    /**
     * Sum grouped counts for a set of statuses.
     *
     * @param  array<string, int>  $counts
     * @param  string[]  $statuses
     */
    protected function countStatuses(array $counts, array $statuses): int
    {
        $total = 0;
        foreach ($statuses as $status) {
            $total += $counts[$status] ?? 0;
        }

        return $total;
    }

    // ── Scholarship Analytics ─────────────────────────────────

    /**
     * @param  array<string, int>|null  $appStatusCounts  Pre-computed application status counts
     */
    public function getScholarshipAnalytics(array $filters, ?array $appStatusCounts = null): array
    {
        $base = $this->baseAppQuery($filters);

        // By scholarship
        $byScholarship = (clone $base)
            ->join('scholarships', 'scholarship_applications.scholarship_id', '=', 'scholarships.id')
            ->select(
                'scholarships.id',
                'scholarships.title',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN scholarship_applications.status = '".ScholarshipApplicationStatus::APPROVED->value."' THEN 1 ELSE 0 END) as approved"),
                DB::raw("SUM(CASE WHEN scholarship_applications.status = '".ScholarshipApplicationStatus::REJECTED->value."' THEN 1 ELSE 0 END) as rejected"),
                DB::raw("SUM(CASE WHEN scholarship_applications.status IN ('".implode("','", self::PENDING_APP_STATUSES)."') THEN 1 ELSE 0 END) as pending")
            )
            ->groupBy('scholarships.id', 'scholarships.title')
            ->orderByDesc('total')
            ->limit(20)
            ->get();

        // Month expression compatible with SQLite and MariaDB/MySQL
        $monthSql = DB::getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', scholarship_applications.submitted_at)"
            : "DATE_FORMAT(scholarship_applications.submitted_at, '%Y-%m')";

        $byMonth = (clone $base)
            ->whereNotNull('scholarship_applications.submitted_at')
            ->selectRaw("{$monthSql} as month, COUNT(*) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Rates — reuse the grouped counts already computed for the filters
        $counts = $appStatusCounts ?? $this->statusCounts(clone $base);
        $approved = $counts[ScholarshipApplicationStatus::APPROVED->value] ?? 0;
        $rejected = $counts[ScholarshipApplicationStatus::REJECTED->value] ?? 0;
        $pending = $this->countStatuses($counts, self::PENDING_APP_STATUSES);
        $decided = $approved + $rejected;

        return [
            'byScholarship' => $byScholarship,
            'byMonth' => $byMonth,
            'approved' => $approved,
            'rejected' => $rejected,
            'pending' => $pending,
            'decided' => $decided,
            'approval_rate' => $decided > 0 ? round(($approved / $decided) * 100, 1) : null,
            'rejection_rate' => $decided > 0 ? round(($rejected / $decided) * 100, 1) : null,
        ];
    }

    // ── Guidance Analytics ────────────────────────────────────

    /**
     * @param  array<string, int>|null  $apptStatusCounts  Pre-computed appointment status counts
     */
    public function getGuidanceAnalytics(array $filters, ?array $apptStatusCounts = null): array
    {
        $base = $this->baseApptQuery($filters);

        // Month expression compatible with SQLite and MariaDB/MySQL
        $monthSql = DB::getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', guidance_appointments.appointment_date)"
            : "DATE_FORMAT(guidance_appointments.appointment_date, '%Y-%m')";

        $byMonth = (clone $base)
            ->whereNotNull('guidance_appointments.appointment_date')
            ->selectRaw("{$monthSql} as month, COUNT(*) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // By type
        $byType = (clone $base)
            ->leftJoin('guidance_appointment_types', 'guidance_appointments.appointment_type_id', '=', 'guidance_appointment_types.id')
            ->select(
                DB::raw('COALESCE(guidance_appointment_types.name, "Unspecified") as type_name'),
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN guidance_appointments.status = '".GuidanceAppointment::STATUS_COMPLETED."' THEN 1 ELSE 0 END) as completed"),
                DB::raw("SUM(CASE WHEN guidance_appointments.status = '".GuidanceAppointment::STATUS_CANCELLED."' THEN 1 ELSE 0 END) as cancelled"),
                DB::raw("SUM(CASE WHEN guidance_appointments.status = '".GuidanceAppointment::STATUS_REQUESTED."' THEN 1 ELSE 0 END) as pending")
            )
            ->groupBy('guidance_appointment_types.name')
            ->orderByDesc('total')
            ->get();

        // By counselor (workload)
        $byCounselor = (clone $base)
            ->join('users', 'guidance_appointments.counselor_id', '=', 'users.id')
            ->select(
                'users.id as counselor_id',
                'users.name as counselor_name',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN guidance_appointments.status = '".GuidanceAppointment::STATUS_REQUESTED."' THEN 1 ELSE 0 END) as pending"),
                DB::raw("SUM(CASE WHEN guidance_appointments.status = '".GuidanceAppointment::STATUS_CONFIRMED."' THEN 1 ELSE 0 END) as confirmed"),
                DB::raw("SUM(CASE WHEN guidance_appointments.status = '".GuidanceAppointment::STATUS_COMPLETED."' THEN 1 ELSE 0 END) as completed"),
                DB::raw("SUM(CASE WHEN guidance_appointments.status = '".GuidanceAppointment::STATUS_CANCELLED."' THEN 1 ELSE 0 END) as cancelled")
            )
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total')
            ->limit(20)
            ->get();

        // Status totals — reuse the grouped counts already computed for the filters
        $counts = $apptStatusCounts ?? $this->statusCounts(clone $base);
        $completed = $counts[GuidanceAppointment::STATUS_COMPLETED] ?? 0;
        $cancelled = $counts[GuidanceAppointment::STATUS_CANCELLED] ?? 0;
        $pending = $counts[GuidanceAppointment::STATUS_REQUESTED] ?? 0;

        return [
            'byMonth' => $byMonth,
            'byType' => $byType,
            'byCounselor' => $byCounselor,
            'completed' => $completed,
            'cancelled' => $cancelled,
            'pending' => $pending,
        ];
    }

    // ── Student Service Analytics ─────────────────────────────

    /**
     * @param  array<string, int>|null  $svcStatusCounts  Pre-computed service request status counts
     */
    public function getServiceAnalytics(array $filters, ?array $svcStatusCounts = null): array
    {
        $base = $this->baseSvcQuery($filters);

        // By type
        $byType = (clone $base)
            ->leftJoin('service_types', 'student_service_requests.service_type_id', '=', 'service_types.id')
            ->select(
                DB::raw('COALESCE(service_types.name, "Unspecified") as type_name'),
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN student_service_requests.status = '".StudentServiceRequest::STATUS_SUBMITTED."' THEN 1 ELSE 0 END) as submitted"),
                DB::raw("SUM(CASE WHEN student_service_requests.status = '".StudentServiceRequest::STATUS_PROCESSING."' THEN 1 ELSE 0 END) as processing"),
                DB::raw("SUM(CASE WHEN student_service_requests.status = '".StudentServiceRequest::STATUS_REQUIRES_INFORMATION."' THEN 1 ELSE 0 END) as requires_info"),
                DB::raw("SUM(CASE WHEN student_service_requests.status = '".StudentServiceRequest::STATUS_COMPLETED."' THEN 1 ELSE 0 END) as completed"),
                DB::raw("SUM(CASE WHEN student_service_requests.status = '".StudentServiceRequest::STATUS_CANCELLED."' THEN 1 ELSE 0 END) as cancelled")
            )
            ->groupBy('service_types.name')
            ->orderByDesc('total')
            ->get();

        // Month expression compatible with SQLite and MariaDB/MySQL
        $monthSql = DB::getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', student_service_requests.submitted_at)"
            : "DATE_FORMAT(student_service_requests.submitted_at, '%Y-%m')";

        $byMonth = (clone $base)
            ->whereNotNull('student_service_requests.submitted_at')
            ->selectRaw("{$monthSql} as month, COUNT(*) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Status totals — reuse the grouped counts already computed for the filters
        $counts = $svcStatusCounts ?? $this->statusCounts(clone $base);
        $completed = $counts[StudentServiceRequest::STATUS_COMPLETED] ?? 0;
        $pending = $this->countStatuses($counts, self::PENDING_SERVICE_STATUSES);

        // Processing time calculation (only completed with both timestamps)
        $durationExpression = DB::getDriverName() === 'sqlite'
            ? "AVG((strftime('%s', student_service_requests.completed_at) - strftime('%s', student_service_requests.submitted_at)) / 3600.0) as avg_hours"
            : 'AVG(TIMESTAMPDIFF(HOUR, student_service_requests.submitted_at, student_service_requests.completed_at)) as avg_hours';

        $processingTime = (clone $base)
            ->where('student_service_requests.status', StudentServiceRequest::STATUS_COMPLETED)
            ->whereNotNull('student_service_requests.submitted_at')
            ->whereNotNull('student_service_requests.completed_at')
            ->selectRaw($durationExpression)
            ->value('avg_hours');

        $avgProcessingHours = $processingTime !== null ? round((float) $processingTime, 1) : null;
        $avgProcessingDays = $avgProcessingHours !== null ? round($avgProcessingHours / 24, 1) : null;

        return [
            'byType' => $byType,
            'byMonth' => $byMonth,
            'completed' => $completed,
            'pending' => $pending,
            'avg_processing_hours' => $avgProcessingHours,
            'avg_processing_days' => $avgProcessingDays,
        ];
    }

    // ── CSV Export Data ───────────────────────────────────────

    public function getExportData(array $filters): array
    {
        [$appCounts, $apptCounts, $svcCounts] = $this->statusCountBundle($filters);

        return [
            'summary' => $this->getSummaryCards($filters, $appCounts, $apptCounts, $svcCounts),
            'scholarships' => $this->getScholarshipAnalytics($filters, $appCounts),
            'guidance' => $this->getGuidanceAnalytics($filters, $apptCounts),
            'services' => $this->getServiceAnalytics($filters, $svcCounts),
        ];
    }

    // ── Filter Options for Views ──────────────────────────────

    public function getFilterOptions(): array
    {
        return [
            'scholarships' => Scholarship::orderBy('title')->pluck('title', 'id'),
            'serviceTypes' => ServiceType::orderBy('name')->pluck('name', 'id'),
            'appointmentTypes' => GuidanceAppointmentType::orderBy('name')->pluck('name', 'id'),
        ];
    }
}
