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

        if (!empty($raw['date_from']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw['date_from'])) {
            $filters['date_from'] = $raw['date_from'];
        }
        if (!empty($raw['date_to']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw['date_to'])) {
            $filters['date_to'] = $raw['date_to'];
        }
        if (isset($filters['date_from'], $filters['date_to']) && $filters['date_from'] > $filters['date_to']) {
            unset($filters['date_from'], $filters['date_to']);
        }

        if (!empty($raw['scholarship_id']) && Scholarship::find($raw['scholarship_id'])) {
            $filters['scholarship_id'] = (int) $raw['scholarship_id'];
        }
        if (!empty($raw['service_type_id']) && ServiceType::find($raw['service_type_id'])) {
            $filters['service_type_id'] = (int) $raw['service_type_id'];
        }
        if (!empty($raw['appointment_type_id']) && GuidanceAppointmentType::find($raw['appointment_type_id'])) {
            $filters['appointment_type_id'] = (int) $raw['appointment_type_id'];
        }

        return $filters;
    }

    // ── Filter Helpers ────────────────────────────────────────

    protected function applyDateFilter(Builder $query, string $dateField, array $filters): Builder
    {
        if (!empty($filters['date_from'])) {
            $query->whereDate($dateField, '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate($dateField, '<=', $filters['date_to']);
        }
        return $query;
    }

    protected function baseAppQuery(array $filters): Builder
    {
        $q = ScholarshipApplication::query()
            ->where('scholarship_applications.status', '!=', ScholarshipApplication::STATUS_DRAFT);
        $this->applyDateFilter($q, 'scholarship_applications.submitted_at', $filters);
        if (!empty($filters['scholarship_id'])) {
            $q->where('scholarship_applications.scholarship_id', $filters['scholarship_id']);
        }
        return $q;
    }

    protected function baseApptQuery(array $filters): Builder
    {
        $q = GuidanceAppointment::query();
        $this->applyDateFilter($q, 'guidance_appointments.appointment_date', $filters);
        if (!empty($filters['appointment_type_id'])) {
            $q->where('guidance_appointments.appointment_type_id', $filters['appointment_type_id']);
        }
        return $q;
    }

    protected function baseSvcQuery(array $filters): Builder
    {
        $q = StudentServiceRequest::query();
        $this->applyDateFilter($q, 'student_service_requests.submitted_at', $filters);
        if (!empty($filters['service_type_id'])) {
            $q->where('student_service_requests.service_type_id', $filters['service_type_id']);
        }
        return $q;
    }

    // ── Main Entry Point ──────────────────────────────────────

    public function getAllAnalytics(array $filters): array
    {
        return [
            'filters'      => $filters,
            'summary'      => $this->getSummaryCards($filters),
            'scholarships' => $this->getScholarshipAnalytics($filters),
            'guidance'     => $this->getGuidanceAnalytics($filters),
            'services'     => $this->getServiceAnalytics($filters),
        ];
    }

    // ── Summary Cards ─────────────────────────────────────────

    public function getSummaryCards(array $filters): array
    {
        $appQ = $this->baseAppQuery($filters);
        $totalApps    = (clone $appQ)->count();
        $pendingApps  = (clone $appQ)->whereIn('scholarship_applications.status', self::PENDING_APP_STATUSES)->count();
        $approvedApps = (clone $appQ)->where('scholarship_applications.status', ScholarshipApplicationStatus::APPROVED->value)->count();
        $rejectedApps = (clone $appQ)->where('scholarship_applications.status', ScholarshipApplicationStatus::REJECTED->value)->count();

        $apptQ = $this->baseApptQuery($filters);
        $totalAppts     = (clone $apptQ)->count();
        $pendingAppts   = (clone $apptQ)->where('guidance_appointments.status', GuidanceAppointment::STATUS_REQUESTED)->count();
        $completedAppts = (clone $apptQ)->where('guidance_appointments.status', GuidanceAppointment::STATUS_COMPLETED)->count();
        $cancelledAppts = (clone $apptQ)->where('guidance_appointments.status', GuidanceAppointment::STATUS_CANCELLED)->count();

        $svcQ = $this->baseSvcQuery($filters);
        $totalSvc      = (clone $svcQ)->count();
        $pendingSvc    = (clone $svcQ)->whereIn('student_service_requests.status', self::PENDING_SERVICE_STATUSES)->count();
        $processingSvc = (clone $svcQ)->where('student_service_requests.status', StudentServiceRequest::STATUS_PROCESSING)->count();
        $completedSvc  = (clone $svcQ)->where('student_service_requests.status', StudentServiceRequest::STATUS_COMPLETED)->count();

        $decided = $approvedApps + $rejectedApps;

        return [
            'total_applications'     => $totalApps,
            'pending_applications'   => $pendingApps,
            'approved_applications'  => $approvedApps,
            'rejected_applications'  => $rejectedApps,
            'approval_rate'          => $decided > 0 ? round(($approvedApps / $decided) * 100, 1) : null,
            'rejection_rate'         => $decided > 0 ? round(($rejectedApps / $decided) * 100, 1) : null,
            'total_appointments'     => $totalAppts,
            'pending_appointments'   => $pendingAppts,
            'completed_appointments' => $completedAppts,
            'cancelled_appointments' => $cancelledAppts,
            'total_service_requests' => $totalSvc,
            'pending_services'       => $pendingSvc,
            'processing_services'    => $processingSvc,
            'completed_services'     => $completedSvc,
        ];
    }

    // ── Scholarship Analytics ─────────────────────────────────

    public function getScholarshipAnalytics(array $filters): array
    {
        $base = $this->baseAppQuery($filters);

        // By scholarship
        $byScholarship = (clone $base)
            ->join('scholarships', 'scholarship_applications.scholarship_id', '=', 'scholarships.id')
            ->select(
                'scholarships.id',
                'scholarships.title',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN scholarship_applications.status = '" . ScholarshipApplicationStatus::APPROVED->value . "' THEN 1 ELSE 0 END) as approved"),
                DB::raw("SUM(CASE WHEN scholarship_applications.status = '" . ScholarshipApplicationStatus::REJECTED->value . "' THEN 1 ELSE 0 END) as rejected"),
                DB::raw("SUM(CASE WHEN scholarship_applications.status IN ('" . implode("','", self::PENDING_APP_STATUSES) . "') THEN 1 ELSE 0 END) as pending")
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

        // Rates
        $approved = (clone $base)->where('scholarship_applications.status', ScholarshipApplicationStatus::APPROVED->value)->count();
        $rejected = (clone $base)->where('scholarship_applications.status', ScholarshipApplicationStatus::REJECTED->value)->count();
        $pending  = (clone $base)->whereIn('scholarship_applications.status', self::PENDING_APP_STATUSES)->count();
        $decided  = $approved + $rejected;

        return [
            'byScholarship'  => $byScholarship,
            'byMonth'        => $byMonth,
            'approved'       => $approved,
            'rejected'       => $rejected,
            'pending'        => $pending,
            'decided'        => $decided,
            'approval_rate'  => $decided > 0 ? round(($approved / $decided) * 100, 1) : null,
            'rejection_rate' => $decided > 0 ? round(($rejected / $decided) * 100, 1) : null,
        ];
    }

    // ── Guidance Analytics ────────────────────────────────────

    public function getGuidanceAnalytics(array $filters): array
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
                DB::raw("SUM(CASE WHEN guidance_appointments.status = '" . GuidanceAppointment::STATUS_COMPLETED . "' THEN 1 ELSE 0 END) as completed"),
                DB::raw("SUM(CASE WHEN guidance_appointments.status = '" . GuidanceAppointment::STATUS_CANCELLED . "' THEN 1 ELSE 0 END) as cancelled"),
                DB::raw("SUM(CASE WHEN guidance_appointments.status = '" . GuidanceAppointment::STATUS_REQUESTED . "' THEN 1 ELSE 0 END) as pending")
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
                DB::raw("SUM(CASE WHEN guidance_appointments.status = '" . GuidanceAppointment::STATUS_REQUESTED . "' THEN 1 ELSE 0 END) as pending"),
                DB::raw("SUM(CASE WHEN guidance_appointments.status = '" . GuidanceAppointment::STATUS_CONFIRMED . "' THEN 1 ELSE 0 END) as confirmed"),
                DB::raw("SUM(CASE WHEN guidance_appointments.status = '" . GuidanceAppointment::STATUS_COMPLETED . "' THEN 1 ELSE 0 END) as completed"),
                DB::raw("SUM(CASE WHEN guidance_appointments.status = '" . GuidanceAppointment::STATUS_CANCELLED . "' THEN 1 ELSE 0 END) as cancelled")
            )
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total')
            ->limit(20)
            ->get();

        $completed = (clone $base)->where('guidance_appointments.status', GuidanceAppointment::STATUS_COMPLETED)->count();
        $cancelled = (clone $base)->where('guidance_appointments.status', GuidanceAppointment::STATUS_CANCELLED)->count();
        $pending   = (clone $base)->where('guidance_appointments.status', GuidanceAppointment::STATUS_REQUESTED)->count();

        return [
            'byMonth'     => $byMonth,
            'byType'      => $byType,
            'byCounselor' => $byCounselor,
            'completed'   => $completed,
            'cancelled'   => $cancelled,
            'pending'     => $pending,
        ];
    }

    // ── Student Service Analytics ─────────────────────────────

    public function getServiceAnalytics(array $filters): array
    {
        $base = $this->baseSvcQuery($filters);

        // By type
        $byType = (clone $base)
            ->leftJoin('service_types', 'student_service_requests.service_type_id', '=', 'service_types.id')
            ->select(
                DB::raw('COALESCE(service_types.name, "Unspecified") as type_name'),
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN student_service_requests.status = '" . StudentServiceRequest::STATUS_SUBMITTED . "' THEN 1 ELSE 0 END) as submitted"),
                DB::raw("SUM(CASE WHEN student_service_requests.status = '" . StudentServiceRequest::STATUS_PROCESSING . "' THEN 1 ELSE 0 END) as processing"),
                DB::raw("SUM(CASE WHEN student_service_requests.status = '" . StudentServiceRequest::STATUS_REQUIRES_INFORMATION . "' THEN 1 ELSE 0 END) as requires_info"),
                DB::raw("SUM(CASE WHEN student_service_requests.status = '" . StudentServiceRequest::STATUS_COMPLETED . "' THEN 1 ELSE 0 END) as completed"),
                DB::raw("SUM(CASE WHEN student_service_requests.status = '" . StudentServiceRequest::STATUS_CANCELLED . "' THEN 1 ELSE 0 END) as cancelled")
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

        $completed = (clone $base)->where('student_service_requests.status', StudentServiceRequest::STATUS_COMPLETED)->count();
        $pending   = (clone $base)->whereIn('student_service_requests.status', self::PENDING_SERVICE_STATUSES)->count();

        // Processing time calculation (only completed with both timestamps)
        $durationExpression = DB::getDriverName() === 'sqlite'
            ? "AVG((strftime('%s', student_service_requests.completed_at) - strftime('%s', student_service_requests.submitted_at)) / 3600.0) as avg_hours"
            : "AVG(TIMESTAMPDIFF(HOUR, student_service_requests.submitted_at, student_service_requests.completed_at)) as avg_hours";

        $processingTime = (clone $base)
            ->where('student_service_requests.status', StudentServiceRequest::STATUS_COMPLETED)
            ->whereNotNull('student_service_requests.submitted_at')
            ->whereNotNull('student_service_requests.completed_at')
            ->selectRaw($durationExpression)
            ->value('avg_hours');

        $avgProcessingHours = $processingTime !== null ? round((float) $processingTime, 1) : null;
        $avgProcessingDays  = $avgProcessingHours !== null ? round($avgProcessingHours / 24, 1) : null;

        return [
            'byType'               => $byType,
            'byMonth'              => $byMonth,
            'completed'            => $completed,
            'pending'              => $pending,
            'avg_processing_hours' => $avgProcessingHours,
            'avg_processing_days'  => $avgProcessingDays,
        ];
    }

    // ── CSV Export Data ───────────────────────────────────────

    public function getExportData(array $filters): array
    {
        return [
            'summary'      => $this->getSummaryCards($filters),
            'scholarships' => $this->getScholarshipAnalytics($filters),
            'guidance'     => $this->getGuidanceAnalytics($filters),
            'services'     => $this->getServiceAnalytics($filters),
        ];
    }

    // ── Filter Options for Views ──────────────────────────────

    public function getFilterOptions(): array
    {
        return [
            'scholarships'      => Scholarship::orderBy('title')->pluck('title', 'id'),
            'serviceTypes'      => ServiceType::orderBy('name')->pluck('name', 'id'),
            'appointmentTypes'  => GuidanceAppointmentType::orderBy('name')->pluck('name', 'id'),
        ];
    }
}