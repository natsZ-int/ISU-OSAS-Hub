<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Scholarship;
use App\Services\ScholarshipSearchService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScholarshipController extends Controller
{
    public function __construct(
        protected ScholarshipSearchService $searchService
    ) {}

    // Show the searchable list of scholarships
    public function index(Request $request): View
    {
        $user = $request->user();
        $profile = $user->studentProfile ?? null;

        // Run search (returns the paginator directly)
        $scholarships = $this->searchService->search($request, $profile);

        // Get dropdown options from the service's existing method
        $options = $this->searchService->availableFilterOptions();
        $types = $options['types'];
        $programs = $options['programs'];

        return view('student.scholarships.index', compact(
            'scholarships', 'types', 'programs', 'profile'
        ));
    }

    // Show details of a single open scholarship
    public function show(Scholarship $scholarship): View
    {
        // Enforce visibility
        abort_unless($scholarship->isOpenForApplication(), 404);

        $scholarship->load('requirements');

        $user = request()->user();
        $profile = $user->studentProfile ?? null;

        // Check if student already applied
        $application = null;
        if ($profile) {
            $application = $scholarship->applications()
                ->where('student_profile_id', $profile->id)
                ->latest()
                ->first();
        }

        return view('student.scholarships.show', compact('scholarship', 'profile', 'application'));
    }
}
