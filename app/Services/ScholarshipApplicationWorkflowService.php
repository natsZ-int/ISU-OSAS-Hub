<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Enums\ScholarshipApplicationStatus;
use App\Exceptions\InvalidTransitionException;
use App\Models\ApplicationDocument;
use App\Models\ApplicationStatusHistory;
use App\Models\ScholarshipApplication;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScholarshipApplicationWorkflowService
{
    private const TRANSITIONS = [
        ScholarshipApplicationStatus::SUBMITTED->value => [
            ScholarshipApplicationStatus::UNDER_REVIEW,
        ],
        ScholarshipApplicationStatus::UNDER_REVIEW->value => [
            ScholarshipApplicationStatus::DOCUMENT_VERIFICATION,
            ScholarshipApplicationStatus::REJECTED,
        ],
        ScholarshipApplicationStatus::DOCUMENT_VERIFICATION->value => [
            ScholarshipApplicationStatus::APPROVED,
            ScholarshipApplicationStatus::REJECTED,
        ],
        ScholarshipApplicationStatus::APPROVED->value => [],
        ScholarshipApplicationStatus::REJECTED->value => [],
    ];

    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function getCurrentState(ScholarshipApplication $application): ?ScholarshipApplicationStatus
    {
        $status = $application->getRawOriginal('status') ?? $application->status;

        if ($status instanceof ScholarshipApplicationStatus) {
            return $status;
        }

        return is_string($status) ? ScholarshipApplicationStatus::tryFrom($status) : null;
    }

    public function getAllowedTransitions(string $currentState): array
    {
        return self::TRANSITIONS[$currentState] ?? [];
    }

    public function canTransition(
        ScholarshipApplication $application,
        string|ScholarshipApplicationStatus $targetState
    ): bool {
        $current = $this->getCurrentState($application);

        if ($current === null) {
            return false;
        }

        $target = $targetState instanceof ScholarshipApplicationStatus
            ? $targetState
            : ScholarshipApplicationStatus::tryFrom((string) $targetState);

        if ($target === null) {
            return false;
        }

        if ($current === $target) {
            return false;
        }

        $allowed = $this->getAllowedTransitions($current->value);

        return in_array($target, $allowed, true);
    }

    public function checkApprovalReadiness(ScholarshipApplication $application): array
    {
        $application->loadMissing([
            'scholarship.requirements',
            'documents',
        ]);

        $requiredRequirements = $application->scholarship->requirements
            ->where('is_required', true);

        if ($requiredRequirements->isEmpty()) {
            return ['canApprove' => true, 'reasons' => []];
        }

        $uploadedDocs = $application->documents
            ->keyBy('scholarship_requirement_id');

        $reasons = [];

        foreach ($requiredRequirements as $req) {
            $doc = $uploadedDocs->get($req->id);

            if (! $doc) {
                $reasons[] = "'{$req->requirement_name}' has not been uploaded.";
            } elseif ($doc->verification_status === ApplicationDocument::VERIFICATION_PENDING) {
                $reasons[] = "'{$req->requirement_name}' is still pending verification.";
            } elseif ($doc->verification_status === ApplicationDocument::VERIFICATION_REJECTED) {
                $reasons[] = "'{$req->requirement_name}' was rejected during verification.";
            }
        }

        return [
            'canApprove' => empty($reasons),
            'reasons' => $reasons,
        ];
    }

    public function transition(
        ScholarshipApplication $application,
        string|ScholarshipApplicationStatus $targetState,
        User $actor,
        ?string $remarks = null,
        ?Request $request = null
    ): ScholarshipApplication {
        if (! $actor->isAdmin() || ! $actor->isActive()) {
            throw new InvalidTransitionException('Only active administrators may change application status.');
        }

        $target = $targetState instanceof ScholarshipApplicationStatus
            ? $targetState
            : ScholarshipApplicationStatus::tryFrom((string) $targetState);

        if ($target === null) {
            throw new InvalidTransitionException('The requested status is not a valid FSM state.');
        }

        $result = DB::transaction(function () use ($application, $target, $actor, $remarks, $request) {
            $locked = ScholarshipApplication::where('id', $application->id)
                ->lockForUpdate()
                ->first();

            if (! $locked) {
                throw new InvalidTransitionException('Application not found.');
            }

            $currentState = $this->getCurrentState($locked);

            if ($currentState === null) {
                throw new InvalidTransitionException('This application has not been submitted yet and cannot be transitioned.');
            }

            $allowed = $this->getAllowedTransitions($currentState->value);

            if (! in_array($target, $allowed, true)) {
                throw new InvalidTransitionException(
                    "Cannot transition from '{$currentState->label()}' to '{$target->label()}'. "
                    .'Allowed transitions: '.implode(', ', array_map(fn ($s) => $s->label(), $allowed)).'.'
                );
            }

            if ($target === ScholarshipApplicationStatus::REJECTED) {
                if (blank($remarks)) {
                    throw new InvalidTransitionException('A rejection reason is required.');
                }
            }

            if ($target === ScholarshipApplicationStatus::APPROVED) {
                $readiness = $this->checkApprovalReadiness($locked);

                if (! $readiness['canApprove']) {
                    $reasonList = implode(' ', $readiness['reasons']);
                    throw new InvalidTransitionException(
                        'This application cannot be approved because required documents are incomplete or not verified. '
                        .$reasonList
                    );
                }
            }

            $previousStatus = is_object($locked->status) ? $locked->status->value : (string) $locked->status;
            $locked->update(['status' => $target->value]);

            ApplicationStatusHistory::create([
                'scholarship_application_id' => $locked->id,
                'previous_status' => $previousStatus,
                'new_status' => $target->value,
                'changed_by' => $actor->id,
                'remarks' => $remarks,
                'created_at' => now(),
            ]);

            $actionLabel = match ($target) {
                ScholarshipApplicationStatus::APPROVED => 'Application Approved',
                ScholarshipApplicationStatus::REJECTED => 'Application Rejected',
                default => 'Application Status Changed',
            };

            AuditLogService::log(
                action: $actionLabel,
                module: 'Scholarship Application Workflow',
                recordType: 'ScholarshipApplication',
                recordId: $locked->id,
                description: "Application '{$locked->application_number}' transitioned from '{$previousStatus}' to '{$target->value}' by {$actor->name}.",
                userId: $actor->id,
                request: $request
            );

            return $locked->fresh();
        });

        // ── Notify the student only after the transaction commits ──
        $this->sendNotification($result, $target, $remarks);

        return $result;
    }

    protected function sendNotification(
        ScholarshipApplication $application,
        ScholarshipApplicationStatus $newStatus,
        ?string $remarks = null,
    ): void {
        $studentUser = $application->studentProfile?->user;

        if (! $studentUser) {
            return;
        }

        $appNumber = $application->application_number;

        [$type, $title, $message, $nextStep] = match ($newStatus) {
            ScholarshipApplicationStatus::UNDER_REVIEW => [
                NotificationType::SCHOLARSHIP_APPLICATION_UNDER_REVIEW,
                'Application Under Review',
                "Your scholarship application {$appNumber} is now under review.",
                'No action is required at this time. We will notify you of any updates.',
            ],
            ScholarshipApplicationStatus::DOCUMENT_VERIFICATION => [
                NotificationType::SCHOLARSHIP_DOCUMENT_VERIFICATION,
                'Document Verification',
                "Your scholarship application {$appNumber} has entered document verification.",
                'Your submitted documents are being verified. Please wait for further updates.',
            ],
            ScholarshipApplicationStatus::APPROVED => [
                NotificationType::SCHOLARSHIP_APPLICATION_APPROVED,
                'Application Approved',
                "Congratulations! Your scholarship application {$appNumber} has been approved.",
                'Please watch for follow-up instructions from OSAS regarding the next steps.',
            ],
            ScholarshipApplicationStatus::REJECTED => [
                NotificationType::SCHOLARSHIP_APPLICATION_REJECTED,
                'Application Rejected',
                "Your scholarship application {$appNumber} was not approved. Please check the application details.",
                'You may review the application details in your dashboard. Contact OSAS if you have questions.',
            ],
            default => [null, null, null, null],
        };

        if (! $type) {
            return;
        }

        // Only include an authorized staff explanation when one was actually provided.
        $reason = $newStatus === ScholarshipApplicationStatus::REJECTED && filled($remarks)
            ? trim((string) $remarks)
            : null;

        $this->notificationService->notifyUser(
            $studentUser,
            $type,
            $title,
            $message,
            'ScholarshipApplication',
            $application->id,
            [
                'reference' => $appNumber,
                'reference_label' => 'Application No.',
                'status' => $newStatus->label(),
                'next_step' => $nextStep,
                'url' => route('student.applications.show', $application->id),
                'reason' => $reason,
            ],
            sendEmail: true,
        );
    }
}
