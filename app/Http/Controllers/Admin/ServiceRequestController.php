<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequestDocument;
use App\Models\ServiceType;
use App\Models\StudentServiceRequest;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\StudentServiceRequestWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ServiceRequestController extends Controller
{
    protected StudentServiceRequestWorkflowService $workflowService;

    public function __construct(StudentServiceRequestWorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
    }

    public function index(Request $request)
    {
        $query = StudentServiceRequest::with(['studentProfile.user', 'serviceType', 'handler']);

        if ($search = $request->filled('search') ? trim((string) $request->input('search')) : null) {
            $like = '%'.addcslashes($search, '\\%_').'%';

            $query->where(function ($q) use ($like) {
                $q->where('request_number', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhere('status', 'like', $like)
                    ->orWhereHas('studentProfile', function ($sub) use ($like) {
                        $sub->where('first_name', 'like', $like)
                            ->orWhere('last_name', 'like', $like)
                            ->orWhere('student_number', 'like', $like);
                    })
                    ->orWhereHas('serviceType', fn ($type) => $type->where('name', 'like', $like));
            });
        }

        if ($request->filled('service_type_id')) {
            $query->where('service_type_id', $request->service_type_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('handler_id')) {
            $query->where('handled_by', $request->handler_id);
        }

        $requests = $query->latest('submitted_at')->paginate(15)->withQueryString();

        $serviceTypes = ServiceType::all();
        $handlers = User::whereHas('role', function ($q) {
            $q->whereIn('name', ['admin', 'counselor']);
        })->where('is_active', true)->get();

        return view('admin.services.requests.index', compact('requests', 'serviceTypes', 'handlers'));
    }

    public function show(StudentServiceRequest $studentServiceRequest)
    {
        $studentServiceRequest->load([
            'studentProfile.user',
            'serviceType.requirements',
            'documents.requirement',
            'histories.user',
            'handler',
            'canceller',
        ]);

        $allowedTransitions = $this->workflowService->getAllowedTransitions($studentServiceRequest);

        $handlers = User::whereHas('role', function ($q) {
            $q->whereIn('name', ['admin', 'counselor']);
        })->where('is_active', true)->get();

        return view('admin.services.requests.show', compact('studentServiceRequest', 'allowedTransitions', 'handlers'));
    }

    public function assign(Request $request, StudentServiceRequest $studentServiceRequest)
    {
        $request->validate([
            'handled_by' => 'required|exists:users,id',
        ]);

        $handler = User::findOrFail($request->handled_by);

        if ($handler->isStudent()) {
            return redirect()->back()->with('error', 'Cannot assign a student to handle service requests.');
        }

        $previousStatus = $studentServiceRequest->status;

        $studentServiceRequest->update([
            'handled_by' => $handler->id,
            'status' => $studentServiceRequest->status === StudentServiceRequest::STATUS_SUBMITTED
                ? StudentServiceRequest::STATUS_PROCESSING
                : $studentServiceRequest->status,
        ]);

        AuditLogService::log(
            action: 'Assigned Service Request',
            module: 'Student Services',
            recordType: 'StudentServiceRequest',
            recordId: $studentServiceRequest->id,
            description: "Request [{$studentServiceRequest->request_number}] assigned to [{$handler->name}]."
        );

        // Only notify the student on the first assignment (submitted -> processing),
        // not on internal re-assignments between staff.
        if ($previousStatus === StudentServiceRequest::STATUS_SUBMITTED
            && $studentServiceRequest->status === StudentServiceRequest::STATUS_PROCESSING) {
            $this->workflowService->notifyAssigned($studentServiceRequest, $handler);
        }

        return redirect()->back()->with('status', "Request assigned to {$handler->name} successfully.");
    }

    public function transition(Request $request, StudentServiceRequest $studentServiceRequest)
    {
        $request->validate([
            'status' => 'required|string',
            'remarks' => 'nullable|string|max:2000',
            'internal_remarks' => 'nullable|string|max:2000',
        ]);

        try {
            $this->workflowService->transition(
                $studentServiceRequest,
                $request->status,
                auth()->user(),
                $request->remarks,
                $request->internal_remarks
            );

            return redirect()->back()->with('status', 'Request status updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function downloadDocument(StudentServiceRequest $studentServiceRequest, ServiceRequestDocument $serviceRequestDocument)
    {
        if ($serviceRequestDocument->student_service_request_id !== $studentServiceRequest->id) {
            abort(403);
        }

        if (! Storage::disk('local')->exists($serviceRequestDocument->file_path)) {
            abort(404, 'The requested document file was not found.');
        }

        return Storage::disk('local')->download($serviceRequestDocument->file_path, $serviceRequestDocument->original_filename);
    }
}
