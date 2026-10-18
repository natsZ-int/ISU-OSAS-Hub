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

        $result = DB::transaction(function () use ($request, $targetStatus, $actor, $remarks, $internalRemarks) {
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

            return $lockedRequest->fresh();
        });

        // ── Notify the student only after the transaction commits ──
        $this->sendNotification($result, $targetStatus, $actor, $remarks);

        return $result;
    }

    /**
     * Notify the student that their request has been assigned for processing.
     *
     * Safe to call repeatedly: the in-app dedupe + `emailed_at` guard prevent
     * duplicate notifications when a request is re-assigned.
     */
    public function notifyAssigned(StudentServiceRequest $request, User $handler): void
    {
        $studentUser = $request->studentProfile?->user;

        if (! $studentUser) {
            return;
        }

        $this->notificationService->notifyUser(
            $studentUser,
            NotificationType::STUDENT_SERVICE_REQUEST_PROCESSING,
            'Request Assigned for Processing',
            "Your service request {$request->request_number} has been assigned and is now being processed by OSAS.",
            'StudentServiceRequest',
            $request->id,
            $this->emailData($request, StudentServiceRequest::STATUS_PROCESSING, [
                'next_step' => 'No action is required at this time. Your request is being processed.',
            ]),
            sendEmail: true,
        );
    }

    protected function sendNotification(
        StudentServiceRequest $request,
        string $newStatus,
        User $actor,
        ?string $remarks = null,
    ): void {
        $studentUser = $request->studentProfile?->user;

        if (!$studentUser) {
            return;
        }

        $reqNumber = $request->request_number;

        [$type, $title, $message, $nextStep] = match ($newStatus) {
            StudentServiceRequest::STATUS_PROCESSING => [
                NotificationType::STUDENT_SERVICE_REQUEST_PROCESSING,
                'Request Under Processing',
                "Your service request {$reqNumber} is now being processed by OSAS.",
                'No action is required at this time. Your request is being processed.',
            ],
            StudentServiceRequest::STATUS_REQUIRES_INFORMATION => [
                NotificationType::STUDENT_SERVICE_ADDITIONAL_INFO_REQUIRED,
                'Action Required: Service Request',
                "Additional information is needed for request {$reqNumber}. Please check the remarks.",
                'Please log in and provide the requested information or documents.',
            ],
            StudentServiceRequest::STATUS_COMPLETED => [
                NotificationType::STUDENT_SERVICE_REQUEST_COMPLETED,
                'Service Request Completed',
                "Your service request {$reqNumber} has been completed.",
                filled($remarks) ? trim((string) $remarks) : 'Your request has been completed. Check your dashboard for any collection instructions.',
            ],
            StudentServiceRequest::STATUS_CANCELLED => [
                NotificationType::STUDENT_SERVICE_REQUEST_CANCELLED,
                'Service Request Cancelled',
                $actor->id === $studentUser->id
                    ? "You cancelled your service request {$reqNumber}."
                    : "Your service request {$reqNumber} was cancelled by OSAS.",
                'No further action is required.',
            ],
            default => [null, null, null, null],
        };

        if (! $type) {
            return;
        }

        // Include an authorized staff explanation when the workflow requires one.
        $reason = in_array($newStatus, [
            StudentServiceRequest::STATUS_REQUIRES_INFORMATION,
            StudentServiceRequest::STATUS_COMPLETED,
        ], true) && filled($remarks)
            ? trim((string) $remarks)
            : null;

        $this->notificationService->notifyUser(
            $studentUser,
            $type,
            $title,
            $message,
            'StudentServiceRequest',
            $request->id,
            $this->emailData($request, $newStatus, [
                'next_step' => $nextStep,
                'reason'    => $reason,
            ]),
            sendEmail: true,
        );
    }

    /**
     * Build the non-confidential metadata shared by service-request emails.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function emailData(StudentServiceRequest $request, string $status, array $overrides = []): array
    {
        $serviceName = $request->serviceType?->name;

        return array_merge([
            'reference'       => $request->request_number,
            'reference_label' => 'Request No.',
            'status'          => $this->statusLabel($status),
            'service'         => $serviceName,
            'url'             => route('student.services.show', $request->id),
        ], $overrides);
    }

    protected function statusLabel(string $status): string
    {
        return match ($status) {
            StudentServiceRequest::STATUS_SUBMITTED            => 'Submitted',
            StudentServiceRequest::STATUS_PROCESSING           => 'Processing',
            StudentServiceRequest::STATUS_REQUIRES_INFORMATION => 'Additional Information Required',
            StudentServiceRequest::STATUS_COMPLETED            => 'Completed',
            StudentServiceRequest::STATUS_CANCELLED            => 'Cancelled',
            default                                            => ucfirst(str_replace('_', ' ', $status)),
        };
    }
}