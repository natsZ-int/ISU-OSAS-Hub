<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\ScholarshipRecommendationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecommendationController extends Controller
{
    public function __construct(
        protected ScholarshipRecommendationService $recommendationService
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
}
