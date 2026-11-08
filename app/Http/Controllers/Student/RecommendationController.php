<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\ScholarshipMatchingService;
use App\Services\ScholarshipRecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecommendationController extends Controller
{
    public function __construct(
        protected ScholarshipRecommendationService $recommendationService,
        protected ScholarshipMatchingService $matchingService,
    ) {}

    public function index(Request $request): View
    {
        $profile = $request->user()->studentProfile;

        if (! $profile) {
            return view('student.recommendations.index', [
                'eligible' => collect(),
                'potential' => collect(),
                'not_eligible' => collect(),
            ]);
        }

        $results = $this->recommendationService->recommendForStudent($profile);

        return view('student.recommendations.index', [
            'eligible' => $results['eligible'],
            'potential' => $results['potential'],
            'not_eligible' => $results['not_eligible'],
        ]);
    }

    /**
     * Ranked matching results straight from the matching engine. The
     * recommendation page uses the richer recommendation service; this
     * endpoint exposes the underlying match scores/factors for clients that
     * need them (e.g. an AJAX "matching details" panel).
     */
    public function matches(Request $request): View|JsonResponse|RedirectResponse
    {
        $profile = $request->user()->studentProfile;

        if (! $profile) {
            if ($request->expectsJson()) {
                return response()->json(['eligible' => [], 'ineligible' => []]);
            }

            return redirect()->route('student.scholarships.recommendations')
                ->with('error', 'Complete your student profile to see matching scholarships.');
        }

        $results = $this->matchingService->recommendForStudent($profile);

        if ($request->expectsJson()) {
            return response()->json([
                'eligible' => $results->get('eligible', collect())->values(),
                'ineligible' => $results->get('ineligible', collect())->values(),
            ]);
        }

        return view('student.recommendations.matches', [
            'eligible' => $results->get('eligible', collect()),
            'ineligible' => $results->get('ineligible', collect()),
        ]);
    }
}
