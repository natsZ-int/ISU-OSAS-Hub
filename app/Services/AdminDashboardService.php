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
        return [
            'active_count'         => Scholarship::where('status', Scholarship::STATUS_OPEN)->count(),
            'total_applications'   => ScholarshipApplication::count(),
            'pending_applications' => ScholarshipApplication::whereIn('status', [
                ScholarshipApplicationStatus::SUBMITTED->value,
                ScholarshipApplicationStatus::UNDER_REVIEW->value,
                ScholarshipApplicationStatus::DOCUMENT_VERIFICATION->value,
            ])->count(),
            'approved_applications' => ScholarshipApplication::where('status', ScholarshipApplicationStatus::APPROVED->value)->count(),
            'rejected_applications' => ScholarshipApplication::where('status', ScholarshipApplicationStatus::REJECTED->value)->count(),
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

        return [
            'upcoming_count'        => GuidanceAppointment::whereIn('status', [GuidanceAppointment::STATUS_REQUESTED, GuidanceAppointment::STATUS_CONFIRMED])
                ->whereDate('appointment_date', '>=', now()->toDateString())
                ->count(),
            'upcoming_items'        => $upcomingAppointments,
            'pending_count'         => GuidanceAppointment::where('status', GuidanceAppointment::STATUS_REQUESTED)->count(),
            'completed_count'       => GuidanceAppointment::where('status', GuidanceAppointment::STATUS_COMPLETED)->count(),
        ];
    }

    protected function getStudentServiceMetrics(): array
    {
        return [
            'pending_count'            => StudentServiceRequest::where('status', StudentServiceRequest::STATUS_SUBMITTED)->count(),
            'processing_count'         => StudentServiceRequest::where('status', StudentServiceRequest::STATUS_PROCESSING)->count(),
            'requires_info_count'      => StudentServiceRequest::where('status', StudentServiceRequest::STATUS_REQUIRES_INFORMATION)->count(),
            'completed_count'          => StudentServiceRequest::where('status', StudentServiceRequest::STATUS_COMPLETED)->count(),
        ];
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