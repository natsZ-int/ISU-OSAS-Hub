<?php

namespace App\Services;

use App\Models\CounselorAvailability;
use App\Models\GuidanceAppointment;
use App\Models\User;

class CounselorDashboardService
{
    /**
     * Gather all dashboard data for the authenticated counselor.
     *
     * All queries are scoped to the counselor's own ID to enforce
     * data ownership at the service layer.
     */
    public function getDashboardData(User $counselor): array
    {
        $today = now()->toDateString();

        // ── Today's Appointments ──
        $todayAppointments = GuidanceAppointment::with(['studentProfile.user', 'appointmentType'])
            ->where('counselor_id', $counselor->id)
            ->whereDate('appointment_date', $today)
            ->whereIn('status', [
                GuidanceAppointment::STATUS_REQUESTED,
                GuidanceAppointment::STATUS_CONFIRMED,
                GuidanceAppointment::STATUS_COMPLETED,
            ])
            ->orderBy('start_time', 'asc')
            ->get();

        // ── Upcoming Appointments (next 5, excluding today) ──
        $upcomingAppointments = GuidanceAppointment::with(['studentProfile.user', 'appointmentType'])
            ->where('counselor_id', $counselor->id)
            ->where('appointment_date', '>', $today)
            ->whereIn('status', [
                GuidanceAppointment::STATUS_REQUESTED,
                GuidanceAppointment::STATUS_CONFIRMED,
            ])
            ->orderBy('appointment_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->limit(5)
            ->get();

        // ── Pending Requests ──
        $pendingCount = GuidanceAppointment::where('counselor_id', $counselor->id)
            ->where('status', GuidanceAppointment::STATUS_REQUESTED)
            ->count();

        $pendingRequests = GuidanceAppointment::with(['studentProfile.user', 'appointmentType'])
            ->where('counselor_id', $counselor->id)
            ->where('status', GuidanceAppointment::STATUS_REQUESTED)
            ->orderBy('appointment_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->limit(5)
            ->get();

        // ── Availability ──
        $availabilities = CounselorAvailability::where('counselor_id', $counselor->id)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        $activeAvailabilityCount = $availabilities->where('is_available', true)->count();

        // ── Completed Appointments ──
        $completedCount = GuidanceAppointment::where('counselor_id', $counselor->id)
            ->where('status', GuidanceAppointment::STATUS_COMPLETED)
            ->count();

        $recentCompleted = GuidanceAppointment::with(['studentProfile.user', 'appointmentType'])
            ->where('counselor_id', $counselor->id)
            ->where('status', GuidanceAppointment::STATUS_COMPLETED)
            ->orderBy('appointment_date', 'desc')
            ->orderBy('start_time', 'desc')
            ->limit(5)
            ->get();

        // ── Unread Notifications ──
        $unreadNotificationCount = $counselor->notifications()->unread()->count();

        return [
            'counselor' => [
                'name' => $counselor->name ?? 'Counselor',
                'role_label' => $counselor->role?->label ?? $counselor->role?->name ?? 'Counselor',
            ],
            'guidance' => [
                'today_appointments' => $todayAppointments,
                'upcoming_appointments' => $upcomingAppointments,
                'pending_requests' => $pendingRequests,
                'pending_request_count' => $pendingCount,
                'availabilities' => $availabilities,
                'active_availability_count' => $activeAvailabilityCount,
                'completed_count' => $completedCount,
                'recent_completed' => $recentCompleted,
            ],
            'unreadNotificationCount' => $unreadNotificationCount,
        ];
    }
}
