<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    /**
     * Allowed module values for filter validation.
     */
    protected array $allowedModules = [
        'authentication',
        'user_management',
        'User Management',
        'scholarship',
        'Scholarship Management',
        'Scholarship Eligibility',
        'Scholarship Application',
        'Scholarship Application Workflow',
        'Scholarship Review',
        'Scholarship Matching',
        'guidance',
        'Guidance Appointment',
        'Guidance Scheduling',
        'student_services',
        'Student Services',
        'notifications',
        'system',
    ];

    public function index(Request $request): View
    {
        Gate::authorize('manage-users');

        $query = AuditLog::with('user');

        // Search
        if ($request->filled('search')) {
            $query->search($request->input('search'));
        }

        // User filter
        if ($request->filled('user_id')) {
            $query->forUser((int) $request->input('user_id'));
        }

        // Module filter
        if ($request->filled('module')) {
            $module = $request->input('module');
            if (in_array($module, $this->allowedModules, true)) {
                $query->forModule($module);
            }
        }

        // Action filter
        if ($request->filled('action')) {
            $query->forAction($request->input('action'));
        }

        // Record type filter
        if ($request->filled('record_type')) {
            $query->forRecordType($request->input('record_type'));
        }

        // Date range filters
        if ($request->filled('date_from')) {
            $query->dateFrom($request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->dateTo($request->input('date_to'));
        }

        // Paginate once: the paginator runs the filtered count itself, so no
        // separate COUNT(*) query is needed for the "total" display.
        $logs = $query->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $totalCount = $logs->total();

        // Filter dropdown data
        $users = User::orderBy('name')->get(['id', 'name', 'email']);
        $modules = AuditLog::distinctModules();
        $actions = AuditLog::distinctActions();
        $recordTypes = AuditLog::distinctRecordTypes();

        return view('admin.audit-logs.index', compact(
            'logs', 'totalCount', 'users', 'modules', 'actions', 'recordTypes'
        ));
    }

    public function show(AuditLog $auditLog): View
    {
        Gate::authorize('manage-users');

        $auditLog->load('user');

        return view('admin.audit-logs.show', compact('auditLog'));
    }
}