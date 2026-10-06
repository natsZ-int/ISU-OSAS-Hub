<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Scholarship;
use App\Services\ScholarshipEligibilityService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EligibilityController extends Controller
{
    public function __construct(
        protected ScholarshipEligibilityService $eligibilityService
    ) {}

    public function show(Request $request, Scholarship $scholarship): View
    {
        $user = $request->user();
        $profile = $user->studentProfile;

        if (! $profile) {
            abort(403, 'You must complete your student profile before checking eligibility.');
        }

        // Students may only preview eligibility for scholarships they can see
        if (! $scholarship->isOpenForApplication() && $scholarship->status !== Scholarship::STATUS_OPEN) {
            abort(404);
        }

        // Get existing application if any (for document readiness)
        $application = $scholarship->applications()
            ->where('student_profile_id', $profile->id)
            ->first();

        $evaluation = $this->eligibilityService->evaluate($profile, $scholarship, $application);

        return view('student.eligibility.show', [
            'scholarship' => $scholarship,
            'evaluation'  => $evaluation,
            'application' => $application,
        ]);
    }
}