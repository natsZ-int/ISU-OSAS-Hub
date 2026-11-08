<?php

namespace App\Http\Controllers\Student;

use App\Enums\NotificationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStudentServiceRequest;
use App\Models\ServiceRequestDocument;
use App\Models\ServiceType;
use App\Models\StudentServiceRequest;
use App\Models\StudentServiceRequestHistory;
use App\Services\AuditLogService;
use App\Services\NotificationService;
use App\Services\ServiceRequestNumberGenerator;
use App\Services\StudentServiceRequestWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class ServiceRequestController extends Controller
{
    protected StudentServiceRequestWorkflowService $workflowService;

    protected ServiceRequestNumberGenerator $numberGenerator;

    public function __construct(
        StudentServiceRequestWorkflowService $workflowService,
        ServiceRequestNumberGenerator $numberGenerator,
        protected NotificationService $notificationService
    ) {
        $this->workflowService = $workflowService;
        $this->numberGenerator = $numberGenerator;
    }

    public function catalog(Request $request)
    {
        $serviceTypes = ServiceType::where('is_active', true)
            ->with('requirements')
            ->orderBy('display_order')
            ->get();

        return view('student.services.catalog', compact('serviceTypes'));
    }

    public function create(ServiceType $serviceType)
    {
        if (! $serviceType->is_active) {
            return redirect()->route('student.services.catalog')->with('error', 'This service is currently unavailable.');
        }

        $serviceType->load('requirements');

        return view('student.services.create', compact('serviceType'));
    }

    public function store(StoreStudentServiceRequest $request)
    {
        $user = auth()->user();
        $studentProfile = $user->studentProfile;

        if (! $studentProfile) {
            return redirect()->back()->with('error', 'Student profile is missing. Please complete your profile first.');
        }

        $serviceType = ServiceType::with('requirements')->findOrFail($request->service_type_id);

        $serviceRequest = DB::transaction(function () use ($request, $user, $studentProfile, $serviceType) {
            $requestNumber = $this->numberGenerator->generate();

            $serviceRequest = StudentServiceRequest::create([
                'request_number' => $requestNumber,
                'student_profile_id' => $studentProfile->id,
                'service_type_id' => $serviceType->id,
                'description' => $request->description,
                'status' => StudentServiceRequest::STATUS_SUBMITTED,
                'submitted_at' => now(),
            ]);

            StudentServiceRequestHistory::create([
                'student_service_request_id' => $serviceRequest->id,
                'previous_status' => null,
                'new_status' => StudentServiceRequest::STATUS_SUBMITTED,
                'changed_by' => $user->id,
                'remarks' => 'Service request submitted by student.',
                'created_at' => now(),
            ]);

            foreach ($serviceType->requirements as $requirement) {
                $inputName = "requirement_{$requirement->id}";
                if ($request->hasFile($inputName)) {
                    $file = $request->file($inputName);
                    $path = $file->store("private/student-service-requests/{$serviceRequest->id}", 'local');

                    ServiceRequestDocument::create([
                        'student_service_request_id' => $serviceRequest->id,
                        'service_type_requirement_id' => $requirement->id,
                        'file_path' => $path,
                        'original_filename' => $file->getClientOriginalName(),
                        'mime_type' => $file->getClientMimeType(),
                        'file_size' => $file->getSize(),
                        'uploaded_by' => $user->id,
                    ]);
                }
            }

            AuditLogService::log(
                action: 'Submitted Service Request',
                module: 'Student Services',
                recordType: 'StudentServiceRequest',
                recordId: $serviceRequest->id,
                description: "Request [{$requestNumber}] submitted for [{$serviceType->name}].",
                userId: $user->id
            );

            return $serviceRequest;
        });

        // Notify the student only after the transaction has committed.
        $this->notificationService->notifyUser(
            $user,
            NotificationType::STUDENT_SERVICE_REQUEST_SUBMITTED,
            'Service Request Submitted',
            "Your {$serviceType->name} request {$serviceRequest->request_number} has been submitted successfully.",
            'StudentServiceRequest',
            $serviceRequest->id,
            [
                'reference' => $serviceRequest->request_number,
                'reference_label' => 'Request No.',
                'status' => 'Submitted',
                'service' => $serviceType->name,
                'next_step' => 'Please wait for OSAS to process your request. You will be notified of any updates.',
                'url' => route('student.services.show', $serviceRequest->id),
            ],
            sendEmail: true,
        );

        return redirect()->route('student.services.show', $serviceRequest)->with('status', 'Your service request has been submitted successfully.');
    }

    public function index(Request $request)
    {
        $studentProfile = auth()->user()->studentProfile;

        if (! $studentProfile) {
            return view('student.services.index', ['requests' => collect([])]);
        }

        $query = StudentServiceRequest::where('student_profile_id', $studentProfile->id)
            ->with('serviceType');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $requests = $query->latest('submitted_at')->paginate(10)->withQueryString();

        return view('student.services.index', compact('requests'));
    }

    public function show(StudentServiceRequest $studentServiceRequest)
    {
        Gate::authorize('view', $studentServiceRequest);

        $studentServiceRequest->load([
            'serviceType.requirements',
            'documents.requirement',
            'histories.user',
        ]);

        return view('student.services.show', compact('studentServiceRequest'));
    }

    public function cancel(StudentServiceRequest $studentServiceRequest)
    {
        Gate::authorize('cancel', $studentServiceRequest);

        try {
            $this->workflowService->transition(
                $studentServiceRequest,
                StudentServiceRequest::STATUS_CANCELLED,
                auth()->user(),
                'Cancelled by Student'
            );

            return redirect()->back()->with('status', 'Your request has been cancelled.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function uploadAdditional(Request $request, StudentServiceRequest $studentServiceRequest)
    {
        Gate::authorize('view', $studentServiceRequest);

        if ($studentServiceRequest->status !== StudentServiceRequest::STATUS_REQUIRES_INFORMATION) {
            return redirect()->back()->with('error', 'Additional document upload is only allowed when information is requested.');
        }

        $request->validate([
            'service_type_requirement_id' => 'nullable|exists:service_type_requirements,id',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $requirement = null;

        if ($request->filled('service_type_requirement_id')) {
            $requirement = $studentServiceRequest->serviceType->requirements()
                ->find($request->service_type_requirement_id);

            if (! $requirement) {
                return redirect()->back()->with('error', 'The selected requirement is not valid for this service type.');
            }
        }

        $requirementId = $requirement?->id;
        $requirementName = $requirement?->name ?? 'Additional Requested Document';

        DB::transaction(function () use ($request, $studentServiceRequest, $requirementId, $requirementName) {
            $file = $request->file('file');
            $path = $file->store("private/student-service-requests/{$studentServiceRequest->id}", 'local');

            $existing = ServiceRequestDocument::where('student_service_request_id', $studentServiceRequest->id)
                ->where('service_type_requirement_id', $requirementId)
                ->first();

            if ($existing) {
                Storage::disk('local')->delete($existing->file_path);
                $existing->delete();
            }

            ServiceRequestDocument::create([
                'student_service_request_id' => $studentServiceRequest->id,
                'service_type_requirement_id' => $requirementId,
                'file_path' => $path,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
                'uploaded_by' => auth()->id(),
            ]);

            StudentServiceRequestHistory::create([
                'student_service_request_id' => $studentServiceRequest->id,
                'previous_status' => $studentServiceRequest->status,
                'new_status' => $studentServiceRequest->status,
                'changed_by' => auth()->id(),
                'remarks' => "Uploaded updated document for requirement: {$requirementName}",
                'created_at' => now(),
            ]);

            AuditLogService::log(
                action: 'Supporting Document Uploaded',
                module: 'Student Services',
                recordType: 'StudentServiceRequest',
                recordId: $studentServiceRequest->id,
                description: "Additional document uploaded for request '{$studentServiceRequest->request_number}', requirement '{$requirementName}'.",
            );
        });

        return redirect()->back()->with('status', 'Supporting document uploaded successfully.');
    }

    public function downloadDocument(StudentServiceRequest $studentServiceRequest, ServiceRequestDocument $serviceRequestDocument)
    {
        Gate::authorize('downloadDocument', $studentServiceRequest);

        if ($serviceRequestDocument->student_service_request_id !== $studentServiceRequest->id) {
            abort(403);
        }

        if (! Storage::disk('local')->exists($serviceRequestDocument->file_path)) {
            abort(404, 'The requested document file was not found.');
        }

        return Storage::disk('local')->download($serviceRequestDocument->file_path, $serviceRequestDocument->original_filename);
    }
}
