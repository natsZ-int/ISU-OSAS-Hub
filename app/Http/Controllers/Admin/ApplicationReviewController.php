<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ScholarshipApplicationStatus;
use App\Exceptions\InvalidTransitionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\TransitionApplicationRequest;
use App\Models\ScholarshipApplication;
use App\Services\ScholarshipApplicationWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ApplicationReviewController extends Controller
{
    public function __construct(
        protected ScholarshipApplicationWorkflowService $workflow
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('manage-users');

        $query = ScholarshipApplication::with([
            'studentProfile.user',
            'scholarship',
        ]);

        if ($search = $request->filled('search') ? $request->input('search') : null) {
            $query->where(function ($q) use ($search) {
                $q->where('application_number', 'like', "%{$search}%")
                  ->orWhereHas('studentProfile', function ($sq) use ($search) {
                      $sq->where('student_number', 'like', "%{$search}%")
                         ->orWhere('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('scholarship', function ($sq) use ($search) {
                      $sq->where('title', 'like', "%{$search}%");
                  });
            });
        }

        if ($status = $request->filled('status') ? $request->input('status') : null) {
            $query->where('status', $status);
        }

        $applications = $query->orderBy('submitted_at', 'desc')
                              ->paginate(15)
                              ->withQueryString();

        return view('admin.applications.index', compact('applications'));
    }

    public function show(ScholarshipApplication $scholarshipApplication): View
    {
        Gate::authorize('manage-users');

        $scholarshipApplication->load([
            'studentProfile.user',
            'scholarship.requirements',
            'documents.requirement',
            'statusHistories.changedBy',
        ]);

        $fsmStatus = $scholarshipApplication->fsmStatus();
        $allowedTransitions = $fsmStatus
            ? $this->workflow->getAllowedTransitions($fsmStatus->value)
            : [];
        $approvalReadiness = $this->workflow->checkApprovalReadiness($scholarshipApplication);

        return view('admin.applications.show', compact(
            'scholarshipApplication',
            'fsmStatus',
            'allowedTransitions',
            'approvalReadiness'
        ));
    }

    public function transition(
        TransitionApplicationRequest $request,
        ScholarshipApplication $scholarshipApplication
    ): RedirectResponse {
        Gate::authorize('manage-users');

        try {
            $this->workflow->transition(
                application: $scholarshipApplication,
                targetState: $request->input('status'),
                actor: $request->user(),
                remarks: $request->input('remarks'),
                request: $request
            );

            return back()->with('success', 'Application status updated successfully.');

        } catch (InvalidTransitionException $e) {
            return back()->withErrors(['transition' => $e->getMessage()]);
        }
    }
}