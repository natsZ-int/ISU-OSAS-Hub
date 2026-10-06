<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRequirementRequest;
use App\Http\Requests\UpdateRequirementRequest;
use App\Models\Scholarship;
use App\Models\ScholarshipRequirement;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class RequirementController extends Controller
{
    public function index(Scholarship $scholarship): View
    {
        Gate::authorize('manage-users');

        $scholarship->load('requirements');

        return view('admin.scholarships.requirements', compact('scholarship'));
    }

    public function store(StoreRequirementRequest $request, Scholarship $scholarship): RedirectResponse
    {
        Gate::authorize('manage-users');

        $requirement = $scholarship->requirements()->create($request->validated());

        AuditLogService::log(
            action: 'Requirement Created',
            module: 'Scholarship Management',
            recordType: 'ScholarshipRequirement',
            recordId: $requirement->id,
            description: "Added requirement '{$requirement->requirement_name}' to '{$scholarship->title}'.",
            request: $request
        );

        return back()->with('success', 'Requirement added.');
    }

    public function update(UpdateRequirementRequest $request, Scholarship $scholarship, ScholarshipRequirement $scholarshipRequirement): RedirectResponse
    {
        Gate::authorize('manage-users');

        if ($scholarshipRequirement->scholarship_id !== $scholarship->id) {
            abort(403);
        }

        $scholarshipRequirement->update($request->validated());

        AuditLogService::log(
            action: 'Requirement Updated',
            module: 'Scholarship Management',
            recordType: 'ScholarshipRequirement',
            recordId: $scholarshipRequirement->id,
            description: "Updated requirement '{$scholarshipRequirement->requirement_name}'.",
            request: $request
        );

        return back()->with('success', 'Requirement updated.');
    }

    public function destroy(Request $request, Scholarship $scholarship, ScholarshipRequirement $scholarshipRequirement): RedirectResponse
    {
        Gate::authorize('manage-users');

        if ($scholarshipRequirement->scholarship_id !== $scholarship->id) {
            abort(403);
        }

        // Check if documents reference this requirement
        $docCount = $scholarshipRequirement->documents()->count();
        if ($docCount > 0) {
            return back()->withErrors(['error' => "Cannot delete: {$docCount} document(s) reference this requirement."]);
        }

        $name = $scholarshipRequirement->requirement_name;
        $scholarshipRequirement->delete();

        AuditLogService::log(
            action: 'Requirement Removed',
            module: 'Scholarship Management',
            recordType: 'ScholarshipRequirement',
            recordId: $scholarshipRequirement->id,
            description: "Removed requirement '{$name}' from '{$scholarship->title}'.",
            request: $request
        );

        return back()->with('success', 'Requirement removed.');
    }
}