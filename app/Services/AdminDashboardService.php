<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\GuidanceAppointment;
use App\Models\Role;
use App\Models\Scholarship;
use App\Models\ScholarshipApplication;
use App\Models\StudentServiceRequest;
use App\Models\User;
use App\Enums\ScholarshipApplicationStatus;
use Illuminate\Database\Eloquent\Builder;

class AdminDashboardService
{
    /**
     * Compile complete operational metrics for the Admin Dashboard.
     */
    public function getMetrics(): array
    {
        return [
            'users' => $this->getUserMetrics(),
            'scholarships' => $this->getScholarshipMetrics(),
            'guidance' => $this->getGuidanceMetrics(),
            'services' => $this->getStudentServiceMetrics(),
            'recent_activity' => $this->getRecentActivity(),
        ];
    }

    protected function getUserMetrics(): array
    {
        // Query counts grouped by role
        $roleCounts = User::join('roles', 'users.role_id', '=', 'roles.id')
            ->selectRaw('roles.name as role_name, count(*) as count')
            ->groupBy('roles.name')
            ->pluck('count', 'role_name')
            ->toArray();

        return [
            'total_students'       => $roleCounts[Role::STUDENT] ?? 0,
            'total_counselors'     => $roleCounts[Role::COUNSELOR] ?? 0,
            'total_administrators' => $roleCounts[Role::ADMIN] ?? 0,
            'active_users'         => User::where('is_active', true)->count(),
        ];
    }

    protected function getScholarshipMetrics(): array
    {
        // One grouped query replaces four separate COUNT(*) queries.
        $counts = $this->statusCounts(ScholarshipApplication::query());

        $pending = 0;
        foreach ([
            ScholarshipApplicationStatus::SUBMITTED->value,
            ScholarshipApplicationStatus::UNDER_REVIEW->value,
            ScholarshipApplicationStatus::DOCUMENT_VERIFICATION->value,
        ] as $status) {
            $pending += $counts[$status] ?? 0;
        }

        return [
            'active_count'          => Scholarship::where('status', Scholarship::STATUS_OPEN)->count(),
            'total_applications'    => array_sum($counts),
            'pending_applications'  => $pending,
            'approved_applications' => $counts[ScholarshipApplicationStatus::APPROVED->value] ?? 0,
            'rejected_applications' => $counts[ScholarshipApplicationStatus::REJECTED->value] ?? 0,
        ];
    }

    protected function getGuidanceMetrics(): array
    {
        $upcomingAppointments = GuidanceAppointment::with(['studentProfile.user', 'counselor'])
            ->whereIn('status', [GuidanceAppointment::STATUS_REQUESTED, GuidanceAppointment::STATUS_CONFIRMED])
            ->whereDate('appointment_date', '>=', now()->toDateString())
            ->orderBy('appointment_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->take(5)
            ->get();

        // One grouped query replaces the separate pending/completed counts.
        $counts = $this->statusCounts(GuidanceAppointment::query());

        return [
            'upcoming_count'  => GuidanceAppointment::whereIn('status', [GuidanceAppointment::STATUS_REQUESTED, GuidanceAppointment::STATUS_CONFIRMED])
                ->whereDate('appointment_date', '>=', now()->toDateString())
                ->count(),
            'upcoming_items'  => $upcomingAppointments,
            'pending_count'   => $counts[GuidanceAppointment::STATUS_REQUESTED] ?? 0,
            'completed_count' => $counts[GuidanceAppointment::STATUS_COMPLETED] ?? 0,
        ];
    }

    protected function getStudentServiceMetrics(): array
    {
        // One grouped query replaces four separate COUNT(*) queries.
        $counts = $this->statusCounts(StudentServiceRequest::query());

        return [
            'pending_count'       => $counts[StudentServiceRequest::STATUS_SUBMITTED] ?? 0,
            'processing_count'    => $counts[StudentServiceRequest::STATUS_PROCESSING] ?? 0,
            'requires_info_count' => $counts[StudentServiceRequest::STATUS_REQUIRES_INFORMATION] ?? 0,
            'completed_count'     => $counts[StudentServiceRequest::STATUS_COMPLETED] ?? 0,
        ];
    }

    /**
     * Count every status of a table in a single GROUP BY query.
     *
     * @return array<string, int>
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

    protected function getRecentActivity(): array
    {
        return [
            'audit_logs' => AuditLog::with('user')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->take(5)
                ->get(),
        ];
    }
}