<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ApplicationStatusHistory;
use App\Models\Scholarship;
use App\Models\ScholarshipApplication;
use App\Services\ApplicationNumberGenerator;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $profile = $request->user()->studentProfile;

        if (! $profile) {
            return view('student.applications.index', [
                'applications' => collect()->paginate(10),
            ]);
        }

        $query = ScholarshipApplication::where('student_profile_id', $profile->id)
            ->with(['scholarship', 'documents']);

        if ($status = $request->filled('status') ? $request->input('status') : null) {
            if (in_array($status, ScholarshipApplication::ALLOWED_STATUSES)) {
                $query->where('status', $status);
            }
        }

        $applications = $query->orderBy('created_at', 'desc')
                              ->paginate(10)
                              ->withQueryString();

        return view('student.applications.index', compact('applications'));
    }

    public function show(ScholarshipApplication $scholarshipApplication): View
    {
        $this->authorizeOwnership($scholarshipApplication);

        $scholarshipApplication->load([
            'scholarship.requirements',
            'documents.requirement',
            'statusHistories.changedBy',
        ]);

        return view('student.applications.show', compact('scholarshipApplication'));
    }

    public function store(Request $request, Scholarship $scholarship): RedirectResponse
    {
        $user = $request->user();
        $profile = $user->studentProfile;

        if (! $profile) {
            return back()->withErrors(['error' => 'You must complete your student profile before applying.']);
        }

        if (! $scholarship->isOpenForApplication()) {
            return back()->withErrors(['error' => 'This scholarship is not currently accepting applications.']);
        }

        // Duplicate check
        $existing = ScholarshipApplication::where('student_profile_id', $profile->id)
            ->where('scholarship_id', $scholarship->id)
            ->first();

        if ($existing) {
            return redirect()->route('student.applications.show', $existing)
                ->with('info', 'You already have an application for this scholarship.');
        }

        DB::beginTransaction();
        try {
            $application = ScholarshipApplication::create([
                'application_number'   => ApplicationNumberGenerator::generate(),
                'student_profile_id'   => $profile->id,
                'scholarship_id'       => $scholarship->id,
                'status'               => ScholarshipApplication::STATUS_DRAFT,
            ]);

            ApplicationStatusHistory::create([
                'scholarship_application_id' => $application->id,
                'previous_status'            => null,
                'new_status'                 => ScholarshipApplication::STATUS_DRAFT,
                'changed_by'                 => $user->id,
                'remarks'                    => 'Application created.',
                'created_at'                 => now(),
            ]);

            DB::commit();

            AuditLogService::log(
                action: 'Application Started',
                module: 'Scholarship Application',
                recordType: 'ScholarshipApplication',
                recordId: $application->id,
                description: "Student '{$user->name}' started application '{$application->application_number}' for '{$scholarship->title}'.",
                request: $request
            );

            return redirect()->route('student.applications.show', $application)
                ->with('success', 'Application draft created. Please upload the required documents.');

        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            if ($e->getCode() === '23000') {
                return back()->withErrors(['error' => 'You already have an application for this scholarship.']);
            }
            return back()->withErrors(['error' => 'Failed to create application. Please try again.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create application. Please try again.']);
        }
    }

    public function submit(Request $request, ScholarshipApplication $scholarshipApplication): RedirectResponse
    {
        $this->authorizeOwnership($scholarshipApplication);

        if (! $scholarshipApplication->isDraft()) {
            return back()->withErrors(['error' => 'This application has already been submitted.']);
        }

        $scholarship = $scholarshipApplication->scholarship;

        if (! $scholarship->isOpenForApplication()) {
            return back()->withErrors(['error' => 'The application period for this scholarship has ended.']);
        }

        if (! $scholarshipApplication->hasAllRequiredDocuments()) {
            return back()->withErrors(['error' => 'Please upload all required documents before submitting.']);
        }

        DB::beginTransaction();
        try {
            $scholarshipApplication->update([
                'status'       => ScholarshipApplication::STATUS_SUBMITTED,
                'submitted_at' => now(),
            ]);

            ApplicationStatusHistory::create([
                'scholarship_application_id' => $scholarshipApplication->id,
                'previous_status'            => ScholarshipApplication::STATUS_DRAFT,
                'new_status'                 => ScholarshipApplication::STATUS_SUBMITTED,
                'changed_by'                 => $request->user()->id,
                'remarks'                    => 'Application submitted by student.',
                'created_at'                 => now(),
            ]);

            DB::commit();

            AuditLogService::log(
                action: 'Application Submitted',
                module: 'Scholarship Application',
                recordType: 'ScholarshipApplication',
                recordId: $scholarshipApplication->id,
                description: "Application '{$scholarshipApplication->application_number}' submitted.",
                request: $request
            );

            return redirect()->route('student.applications.show', $scholarshipApplication)
                ->with('success', 'Application submitted successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to submit application.']);
        }
    }

    protected function authorizeOwnership(ScholarshipApplication $application): void
    {
        $profile = auth()->user()->studentProfile;

        if (! $profile || $application->student_profile_id !== $profile->id) {
            abort(403, 'You are not authorized to access this application.');
        }
    }
}