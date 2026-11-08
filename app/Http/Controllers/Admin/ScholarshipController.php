<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreScholarshipRequest;
use App\Http\Requests\UpdateScholarshipRequest;
use App\Models\Scholarship;
use App\Services\AuditLogService;
use App\Services\ScholarshipAnnouncementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ScholarshipController extends Controller
{
    public function __construct(
        private ScholarshipAnnouncementService $announcementService,
    ) {}

    /**
     * Display a paginated list of scholarships with optional filters.
     */
    public function index(Request $request): View
    {
        Gate::authorize('manage-users'); // Reuses existing admin privilege gate

        $query = Scholarship::withCount('applications');

        // Search filter
        if ($search = $request->filled('search') ? $request->input('search') : null) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('provider', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($status = $request->filled('status') ? $request->input('status') : null) {
            if (in_array($status, Scholarship::ALLOWED_STATUSES)) {
                $query->where('status', $status);
            }
        }

        // Scholarship Type filter
        if ($type = $request->filled('type') ? $request->input('type') : null) {
            $query->where('scholarship_type', $type);
        }

        $scholarships = $query->orderBy('created_at', 'desc')
            ->paginate(12)
            ->withQueryString();

        $types = Scholarship::distinct()->pluck('scholarship_type');

        return view('admin.scholarships.index', compact('scholarships', 'types'));
    }

    /**
     * Show the form for creating a new scholarship.
     */
    public function create(): View
    {
        Gate::authorize('manage-users');

        return view('admin.scholarships.create');
    }

    /**
     * Store a newly created scholarship in the database with parsed eligibility criteria.
     */
    public function store(StoreScholarshipRequest $request): RedirectResponse
    {
        Gate::authorize('manage-users');

        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        // Parse comma-separated text into structured JSON arrays for eligibility checks
        $data['eligible_programs'] = $this->parseCsv($request->input('eligible_programs_text'));
        $data['eligible_year_levels'] = $this->parseCsvInts($request->input('eligible_year_levels_text'));

        $scholarship = Scholarship::create($data);

        AuditLogService::log(
            action: 'Scholarship Created',
            module: 'Scholarship Management',
            recordType: 'Scholarship',
            recordId: $scholarship->id,
            description: "Created scholarship '{$scholarship->title}'.",
            request: $request
        );

        if ($scholarship->status === Scholarship::STATUS_OPEN) {
            $this->announce($scholarship, $request);
        }

        return redirect()->route('admin.scholarships.index')
            ->with('status', "Scholarship '{$scholarship->title}' created successfully.");
    }

    /**
     * Show the form for editing the specified scholarship.
     */
    public function edit(Scholarship $scholarship): View
    {
        Gate::authorize('manage-users');
        $scholarship->load('requirements');

        return view('admin.scholarships.edit', compact('scholarship'));
    }

    /**
     * Update the specified scholarship's details and eligibility rules.
     */
    public function update(UpdateScholarshipRequest $request, Scholarship $scholarship): RedirectResponse
    {
        Gate::authorize('manage-users');

        $data = $request->validated();

        // Parse modified comma-separated inputs
        $data['eligible_programs'] = $this->parseCsv($request->input('eligible_programs_text'));
        $data['eligible_year_levels'] = $this->parseCsvInts($request->input('eligible_year_levels_text'));

        $scholarship->update($data);

        AuditLogService::log(
            action: 'Scholarship Updated',
            module: 'Scholarship Eligibility',
            recordType: 'Scholarship',
            recordId: $scholarship->id,
            description: "Updated scholarship '{$scholarship->title}' (eligibility criteria may have changed).",
            request: $request
        );

        // Editing the criteria of an open scholarship can make students who
        // were previously ineligible qualify. Notify only those newly
        // eligible students so nobody receives the same announcement twice.
        $eligibilityChanged = $scholarship->wasChanged([
            'status',
            'minimum_gwa',
            'maximum_family_income',
            'eligible_programs',
            'eligible_year_levels',
            'required_enrollment_status',
            'application_start_date',
            'application_deadline',
        ]);

        if ($scholarship->status === Scholarship::STATUS_OPEN && $eligibilityChanged) {
            $this->announceNewlyEligible($scholarship, $request);
        }

        return redirect()->route('admin.scholarships.edit', $scholarship)
            ->with('status', 'Scholarship updated successfully.');
    }

    /**
     * Move a scholarship status to open.
     */
    public function activate(Request $request, Scholarship $scholarship): RedirectResponse
    {
        Gate::authorize('manage-users');

        $wasOpen = $scholarship->status === Scholarship::STATUS_OPEN;

        $scholarship->update(['status' => Scholarship::STATUS_OPEN]);

        AuditLogService::log(
            action: 'Scholarship Activated',
            module: 'Scholarship Management',
            recordType: 'Scholarship',
            recordId: $scholarship->id,
            description: "Activated scholarship '{$scholarship->title}'.",
            request: $request
        );

        // Only announce on an actual transition into the open state so
        // re-activating an already-open scholarship never re-notifies students.
        if (! $wasOpen) {
            $this->announce($scholarship, $request);
        }

        return back()->with('status', 'Scholarship has been activated.');
    }

    /**
     * Manually notify eligible students about an open scholarship.
     *
     * Useful when staff want to send a reminder after changing requirements or
     * the deadline without waiting for an automatic announcement.
     */
    public function announceNow(Request $request, Scholarship $scholarship): RedirectResponse
    {
        Gate::authorize('manage-users');

        if ($scholarship->status !== Scholarship::STATUS_OPEN) {
            return back()->with('error', 'Only open scholarships can be announced to students.');
        }

        $recipients = $this->announcementService->announce($scholarship);

        AuditLogService::log(
            action: 'Scholarship Announced',
            module: 'Scholarship Management',
            recordType: 'Scholarship',
            recordId: $scholarship->id,
            description: "Manually notified {$recipients} eligible student(s) about scholarship '{$scholarship->title}'.",
            request: $request
        );

        return back()->with(
            'status',
            $recipients === 0
                ? 'No eligible students need a new notification for this scholarship.'
                : "Notified {$recipients} eligible student(s) about '{$scholarship->title}'."
        );
    }

    /**
     * Move a scholarship status to closed.
     */
    public function deactivate(Request $request, Scholarship $scholarship): RedirectResponse
    {
        Gate::authorize('manage-users');

        $scholarship->update(['status' => Scholarship::STATUS_CLOSED]);

        AuditLogService::log(
            action: 'Scholarship Deactivated',
            module: 'Scholarship Management',
            recordType: 'Scholarship',
            recordId: $scholarship->id,
            description: "Deactivated scholarship '{$scholarship->title}'.",
            request: $request
        );

        return back()->with('status', 'Scholarship has been deactivated.');
    }

    /**
     * Notify eligible students about a scholarship and record the outcome.
     */
    private function announce(Scholarship $scholarship, Request $request): void
    {
        $recipients = $this->announcementService->announce($scholarship);

        AuditLogService::log(
            action: 'Scholarship Announced',
            module: 'Scholarship Management',
            recordType: 'Scholarship',
            recordId: $scholarship->id,
            description: "Notified {$recipients} eligible student(s) about scholarship '{$scholarship->title}'.",
            request: $request
        );
    }

    /**
     * Notify only students who became eligible after a criteria change.
     */
    private function announceNewlyEligible(Scholarship $scholarship, Request $request): void
    {
        $recipients = $this->announcementService->announceToNewlyEligible($scholarship);

        if ($recipients === 0) {
            return;
        }

        AuditLogService::log(
            action: 'Scholarship Announced',
            module: 'Scholarship Management',
            recordType: 'Scholarship',
            recordId: $scholarship->id,
            description: "Notified {$recipients} newly eligible student(s) about scholarship '{$scholarship->title}'.",
            request: $request
        );
    }

    /**
     * Helper: Parse comma-separated strings to normalized, unique arrays.
     */
    private function parseCsv(?string $input): ?array
    {
        if (blank($input)) {
            return null;
        }
        $items = array_filter(array_map('trim', explode(',', $input)));

        return empty($items) ? null : array_values(array_unique($items));
    }

    /**
     * Helper: Parse comma-separated strings to unique arrays of strictly positive integers.
     */
    private function parseCsvInts(?string $input): ?array
    {
        if (blank($input)) {
            return null;
        }
        $items = array_filter(array_map(fn ($v) => (int) trim($v), explode(',', $input)), fn ($v) => $v > 0);

        return empty($items) ? null : array_values(array_unique($items));
    }
}
