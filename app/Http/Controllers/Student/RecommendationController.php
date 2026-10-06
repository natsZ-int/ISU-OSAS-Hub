<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\ScholarshipMatchingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecommendationController extends Controller
{
    public function __construct(
        protected ScholarshipMatchingService $matchingService
    ) {}

    public function index(Request $request): View
    {
        $profile = $request->user()->studentProfile;

        if (! $profile) {
            return view('student.recommendations.index', [
                'eligible'   => collect(),
                'ineligible' => collect(),
            ]);
        }

        $results = $this->matchingService->recommendForStudent($profile);

        return view('student.recommendations.index', [
            'eligible'   => $results->get('eligible', collect()),
            'ineligible' => $results->get('ineligible', collect()),
        ]);
    }
}