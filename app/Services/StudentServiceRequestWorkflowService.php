<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Models\Notification;
use App\Models\StudentServiceRequest;
use App\Models\StudentServiceRequestHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StudentServiceRequestWorkflowService
{
    protected AuditLogService $auditLogService;
    protected NotificationService $notificationService;

    public function __construct(
        AuditLogService $auditLogService,
        NotificationService $notificationService
    ) {
        $this->auditLogService = $auditLogService;
        $this->notificationService = $notificationService;
    }

    protected array $transitions = [
        StudentServiceRequest::STATUS_SUBMITTED => [
            StudentServiceRequest::STATUS_PROCESSING,
            StudentServiceRequest::STATUS_REQUIRES_INFORMATION,
            StudentServiceRequest::STATUS_CANCELLED,
        ],
        StudentServiceRequest::STATUS_PROCESSING => [
            StudentServiceRequest::STATUS_REQUIRES_INFORMATION,
            StudentServiceRequest::STATUS_COMPLETED,
            StudentServiceRequest::STATUS_CANCELLED,
        ],
        StudentServiceRequest::STATUS_REQUIRES_INFORMATION => [
            StudentServiceRequest::STATUS_PROCESSING,
            StudentServiceRequest::STATUS_CANCELLED,
        ],
        StudentServiceRequest::STATUS_COMPLETED => [],
        StudentServiceRequest::STATUS_CANCELLED => [],
    ];

    public function getAllowedTransitions(StudentServiceRequest $request): array
    {
        return $this->transitions[$request->status] ?? [];
    }

    public function canTransition(StudentServiceRequest $request, string $targetStatus, User $actor): bool
    {
        if ($request->status === $targetStatus) {
            return false;
        }

        $allowed = $this->getAllowedTransitions($request);
        if (!in_array($targetStatus, $allowed, true)) {
            return false;
        }

        if ($actor->isStudent()) {
            if ($targetStatus === StudentServiceRequest::STATUS_CANCELLED) {
                return $request->studentProfile && $request->studentProfile->user_id === $actor->id;
            }
            return false;
        }

        if ($actor->isAdmin()) {
            return true;
        }

        if ($actor->isCounselor()) {
            return $request->handled_by === $actor->id;
        }

        return false;
    }

    public function transition(
        StudentServiceRequest $request,
        string $targetStatus,
        User $actor,
        ?string $remarks = null,
        ?string $internalRemarks = null
    ): StudentServiceRequest {
        if (!$this->canTransition($request, $targetStatus, $actor)) {
            throw new InvalidArgumentException("Transition from [{$request->status}] to [{$targetStatus}] is not permitted for this user.");
        }

        if ($targetStatus === StudentServiceRequest::STATUS_REQUIRES_INFORMATION && empty(trim((string) $remarks))) {
            throw new InvalidArgumentException("A clear explanation is required when requesting additional information from the student.");
        }

        if ($targetStatus === StudentServiceRequest::STATUS_COMPLETED && empty(trim((string) $remarks))) {
            throw new InvalidArgumentException("Completion remarks are required when marking a request completed.");
        }

        return DB::transaction(function () use ($request, $targetStatus, $actor, $remarks, $internalRemarks) {
            $lockedRequest = StudentServiceRequest::where('id', $request->id)->lockForUpdate()->firstOrFail();
            $previousStatus = $lockedRequest->status;

            $updateData = [
                'status'  => $targetStatus,
                'remarks' => $remarks ?: $lockedRequest->remarks,
            ];

            if ($internalRemarks !== null) {
                $updateData['internal_remarks'] = $internalRemarks;
            }

            if ($targetStatus === StudentServiceRequest::STATUS_COMPLETED) {
                $updateData['completed_at'] = now();
            }

            if ($targetStatus === StudentServiceRequest::STATUS_CANCELLED) {
                $updateData['cancelled_at'] = now();
                $updateData['cancelled_by'] = $actor->id;
            }

            $lockedRequest->update($updateData);

            StudentServiceRequestHistory::create([
                'student_service_request_id' => $lockedRequest->id,
                'previous_status'            => $previousStatus,
                'new_status'                 => $targetStatus,
                'changed_by'                 => $actor->id,
                'remarks'                    => $remarks,
                'created_at'                 => now(),
            ]);

            AuditLogService::log(
                action: 'Updated Service Request Status',
                module: 'Student Services',
                recordType: 'StudentServiceRequest',
                recordId: $lockedRequest->id,
                description: "Request [{$lockedRequest->request_number}] transitioned from [{$previousStatus}] to [{$targetStatus}].",
                userId: $actor->id
            );

            $this->sendNotification($lockedRequest, $targetStatus, $actor);

            return $lockedRequest->fresh();
        });
    }

    protected function sendNotification(StudentServiceRequest $request, string $newStatus, User $actor): void
    {
        $studentUser = $request->studentProfile?->user;

        if (!$studentUser) {
            return;
        }

        $reqNumber = $request->request_number;

        [$type, $title, $message] = match ($newStatus) {
            StudentServiceRequest::STATUS_PROCESSING => [
                NotificationType::STUDENT_SERVICE_REQUEST_PROCESSING,
                'Request Under Processing',
                "Your service request {$reqNumber} is now being processed by OSAS.",
            ],
            StudentServiceRequest::STATUS_REQUIRES_INFORMATION => [
                NotificationType::STUDENT_SERVICE_ADDITIONAL_INFO_REQUIRED,
                'Action Required: Service Request',
                "Additional information is needed for request {$reqNumber}. Please check the remarks.",
            ],
            StudentServiceRequest::STATUS_COMPLETED => [
                NotificationType::STUDENT_SERVICE_REQUEST_COMPLETED,
                'Service Request Completed',
                "Your service request {$reqNumber} has been completed.",
            ],
            StudentServiceRequest::STATUS_CANCELLED => [
                NotificationType::STUDENT_SERVICE_REQUEST_CANCELLED,
                'Service Request Cancelled',
                $actor->id === $studentUser->id
                    ? "You cancelled your service request {$reqNumber}."
                    : "Your service request {$reqNumber} was cancelled by OSAS.",
            ],
            default => [null, null, null],
        };

        if ($type) {
            $this->notificationService->notifyUser(
                $studentUser,
                $type,
                $title,
                $message,
                'StudentServiceRequest',
                $request->id
            );
        }
    }
}