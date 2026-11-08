<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Scholarship;
use App\Services\ScholarshipSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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

    /**
     * Alias used by the quick search boxes. Both GET /scholarships?search=...
     * and this endpoint go through the same service, so results stay
     * consistent with the full filter page.
     */
    public function search(Request $request): View
    {
        return $this->index($request);
    }

    /**
     * Lightweight quick-search endpoint. A normal form submit is redirected
     * to the search page; AJAX/JSON callers receive the matching list.
     */
    public function quickSearch(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'search' => ['nullable', 'string', 'max:200'],
            'keyword' => ['nullable', 'string', 'max:200'],
            'query' => ['nullable', 'string', 'max:200'],
        ]);

        $term = $request->input('q')
            ?? $request->input('search')
            ?? $request->input('keyword')
            ?? $request->input('query');

        $profile = $request->user()->studentProfile ?? null;

        if ($request->expectsJson() || $request->ajax()) {
            $results = $this->searchService->quickSearch(
                is_string($term) ? $term : null,
                $profile,
                10
            );

            return response()->json([
                'query' => is_string($term) ? trim($term) : '',
                'count' => $results->count(),
                'results' => $results->map(function (Scholarship $scholarship) {
                    $match = $scholarship->match_result ?? null;

                    return [
                        'id' => $scholarship->id,
                        'title' => $scholarship->title,
                        'subtitle' => $scholarship->provider,
                        'provider' => $scholarship->provider,
                        'type' => $scholarship->scholarship_type,
                        'meta' => trim(implode(' · ', array_filter([
                            $scholarship->scholarship_type,
                            $scholarship->application_deadline
                                ? 'Deadline '.$scholarship->application_deadline->format('M d, Y')
                                : null,
                        ]))),
                        'deadline' => $scholarship->application_deadline?->format('M d, Y'),
                        'eligible' => $match?->isEligible(),
                        'match_score' => $match?->matchScore,
                        'badge' => $match?->isEligible() ? 'Eligible' : null,
                        'badge_tone' => $match?->isEligible() ? 'active' : 'inactive',
                        'search' => $scholarship->title,
                        'avatar' => \Illuminate\Support\Str::upper(
                            \Illuminate\Support\Str::substr($scholarship->title, 0, 1)
                        ),
                        'url' => route('student.scholarships.show', $scholarship),
                    ];
                })->values(),
            ]);
        }

        if (! is_string($term) || trim($term) === '') {
            return redirect()->route('student.scholarships.index');
        }

        return redirect()->route('student.scholarships.index', [
            'search' => trim($term),
        ]);
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
