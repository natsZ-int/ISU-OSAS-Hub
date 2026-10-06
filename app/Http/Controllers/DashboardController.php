<?php

namespace App\Http\Controllers;

use App\Services\AdminDashboardService;
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

    public function counselor(): View
    {
        return view('dashboards.counselor');
    }

    public function student(): View
    {
        return view('dashboards.student');
    }
}