<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateMatchingWeightsRequest;
use App\Models\ScholarshipMatchingWeight;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MatchingWeightController extends Controller
{
    public function edit(): View
    {
        Gate::authorize('manage-users');
        $weights = ScholarshipMatchingWeight::active() ?? new ScholarshipMatchingWeight;

        return view('admin.matching.weights', compact('weights'));
    }

    public function update(UpdateMatchingWeightsRequest $request): RedirectResponse
    {
        Gate::authorize('manage-users');

        ScholarshipMatchingWeight::where('is_active', true)->update(['is_active' => false]);

        $weights = ScholarshipMatchingWeight::create([
            'academic_weight' => $request->academic_weight,
            'financial_weight' => $request->financial_weight,
            'program_weight' => $request->program_weight,
            'year_level_weight' => $request->year_level_weight,
            'document_readiness_weight' => $request->document_readiness_weight,
            'is_active' => true,
            'updated_by' => $request->user()->id,
        ]);

        AuditLogService::log(
            action: 'Matching weights updated',
            module: 'Scholarship Matching',
            recordType: 'ScholarshipMatchingWeight',
            recordId: $weights->id,
            description: "Matching weights updated: Academic={$weights->academic_weight}, Financial={$weights->financial_weight}, Program={$weights->program_weight}, YearLevel={$weights->year_level_weight}, DocReadiness={$weights->document_readiness_weight}.",
            request: $request
        );

        return back()->with('status', 'Matching weights updated successfully.');
    }
}
