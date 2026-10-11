<?php

namespace App\Http\Controllers;

use App\Services\AdminDashboardService;
use App\Services\CounselorDashboardService;
use App\Services\ScholarshipMatchingService;
use App\Models\ScholarshipApplication;
use App\Models\GuidanceAppointment;
use App\Models\StudentServiceRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    protected AdminDashboardService $adminDashboardService;

    public function __construct(AdminDashboardService $adminDashboardService)
    {
        $this->adminDashboardService = $adminDashboardService;
    }

    public function admin(): View
    {
        $metrics = $this->adminDashboardService->getMetrics();

        return view('dashboards.admin', compact('metrics'));
    }

    public function counselor(CounselorDashboardService $counselorDashboardService): View
    {
        $user = Auth::user();
        $data = $counselorDashboardService->getDashboardData($user);

        return view('dashboards.counselor', $data);
    }

    public function student(ScholarshipMatchingService $matchingService): View
    {
        $user = Auth::user();
        $profile = $user->studentProfile;

        if (!$profile) {
            return view('dashboards.student', [
                'profile' => null,
                'unreadNotificationCount' => $user->notifications()->unread()->count(),
                'scholarshipData' => [
                    'recommendations' => collect(),
                    'activeCount' => 0,
                    'draftCount' => 0,
                    'recent' => null,
                ],
                'guidanceData' => [
                    'upcoming' => null,
                ],
                'servicesData' => [
                    'activeCount' => 0,
                    'recentCompleted' => null,
                ]
            ]);
        }

        $recommendationsResult = $matchingService->recommendForStudent($profile);
        $recommendations = $recommendationsResult->get('eligible', collect())->take(3);

        $activeStatuses = ['submitted', 'under_review', 'document_verification'];

        $activeApplicationsCount = ScholarshipApplication::where('student_profile_id', $profile->id)
            ->whereIn('status', $activeStatuses)
            ->count();

        $draftApplicationsCount = ScholarshipApplication::where('student_profile_id', $profile->id)
            ->where('status', 'draft')
            ->count();

        $recentlyUpdatedApplication = ScholarshipApplication::with('scholarship')
            ->where('student_profile_id', $profile->id)
            ->whereIn('status', $activeStatuses)
            ->orderBy('updated_at', 'desc')
            ->first();

        $upcomingAppointment = GuidanceAppointment::with('counselor', 'appointmentType')
            ->where('student_profile_id', $profile->id)
            ->where('appointment_date', '>=', now()->toDateString())
            ->whereIn('status', ['requested', 'confirmed'])
            ->orderBy('appointment_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->first();

        $activeServiceStatuses = ['submitted', 'processing', 'requires_information'];

        $activeServicesCount = StudentServiceRequest::where('student_profile_id', $profile->id)
            ->whereIn('status', $activeServiceStatuses)
            ->count();

        $recentCompletedService = StudentServiceRequest::with('serviceType')
            ->where('student_profile_id', $profile->id)
            ->where('status', 'completed')
            ->orderBy('completed_at', 'desc')
            ->first();

        $unreadNotificationCount = $user->notifications()->unread()->count();

        return view('dashboards.student', [
            'profile' => $profile,
            'unreadNotificationCount' => $unreadNotificationCount,
            'scholarshipData' => [
                'recommendations' => $recommendations,
                'activeCount' => $activeApplicationsCount,
                'draftCount' => $draftApplicationsCount,
                'recent' => $recentlyUpdatedApplication,
            ],
            'guidanceData' => [
                'upcoming' => $upcomingAppointment,
            ],
            'servicesData' => [
                'activeCount' => $activeServicesCount,
                'recentCompleted' => $recentCompletedService,
            ]
        ]);
    }
}