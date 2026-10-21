<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Models\GuidanceAppointment;
use App\Models\GuidanceSchedulingSettings;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GuidanceAppointmentWorkflowService
{
    protected const TRANSITIONS = [
        GuidanceAppointment::STATUS_REQUESTED => [
            GuidanceAppointment::STATUS_CONFIRMED => 'counselor',
            GuidanceAppointment::STATUS_REJECTED => 'counselor',
            GuidanceAppointment::STATUS_CANCELLED => 'student',
        ],
        GuidanceAppointment::STATUS_CONFIRMED => [
            GuidanceAppointment::STATUS_COMPLETED => 'counselor',
            GuidanceAppointment::STATUS_CANCELLED => 'student',
        ],
        GuidanceAppointment::STATUS_COMPLETED => [],
        GuidanceAppointment::STATUS_CANCELLED => [],
        GuidanceAppointment::STATUS_REJECTED => [],
    ];

    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function getAllowedTransitions(GuidanceAppointment $appointment): array
    {
        return array_keys(self::TRANSITIONS[$appointment->status] ?? []);
    }

    public function canTransition(
        GuidanceAppointment $appointment,
        string $targetState,
        User $actor
    ): bool {
        $allowed = self::TRANSITIONS[$appointment->status] ?? [];

        if (! isset($allowed[$targetState])) {
            return false;
        }

        $requiredRole = $allowed[$targetState];
        $actorRole = strtolower($actor->role?->name ?? '');

        if ($requiredRole === 'counselor') {
            $isCounselor = $actorRole === 'counselor' || $actor->isCounselor() || $actor->hasRole('counselor');

            return $isCounselor && (int) $appointment->counselor_id === (int) $actor->id;
        }

        if ($requiredRole === 'student') {
            $isStudent = $actorRole === 'student' || $actor->isStudent() || $actor->hasRole('student');
            $profile = $actor->studentProfile;

            return $isStudent && $profile && (int) $appointment->student_profile_id === (int) $profile->id;
        }

        return false;
    }

    public function transition(
        GuidanceAppointment $appointment,
        string $targetState,
        User $actor,
        ?string $remarks = null
    ): GuidanceAppointment {
        if (! $this->canTransition($appointment, $targetState, $actor)) {
            throw new \InvalidArgumentException(
                "Transition from '{$appointment->status}' to '{$targetState}' is not allowed for this user."
            );
        }

        if ($targetState === GuidanceAppointment::STATUS_REJECTED) {
            if (blank($remarks) || strlen(trim($remarks)) < 5) {
                throw new \InvalidArgumentException(
                    'A meaningful rejection reason is required (at least 5 characters).'
                );
            }
        }

        $actorRole = strtolower($actor->role?->name ?? '');
        if ($targetState === GuidanceAppointment::STATUS_CANCELLED && ($actorRole === 'student' || $actor->isStudent() || $actor->hasRole('student'))) {
            $this->validateCancellationNotice($appointment);
        }

        if ($targetState === GuidanceAppointment::STATUS_COMPLETED) {
            $endDateTime = $appointment->appointment_date->copy()
                ->setTimeFromTimeString($appointment->end_time);
            if (now()->lt($endDateTime)) {
                throw new \InvalidArgumentException(
                    'Cannot complete an appointment before its scheduled end time.'
                );
            }
        }

        $result = DB::transaction(function () use ($appointment, $targetState, $actor, $remarks) {
            $previousStatus = $appointment->status;

            $updateData = ['status' => $targetState];

            if ($targetState === GuidanceAppointment::STATUS_REJECTED) {
                $updateData['rejection_reason'] = trim($remarks);
            }

            if ($targetState === GuidanceAppointment::STATUS_CANCELLED) {
                $updateData['cancelled_at'] = now();
                $updateData['cancelled_by'] = $actor->id;
            }

            $appointment->update($updateData);

            AuditLogService::log(
                action: 'Appointment '.ucfirst($targetState),
                module: 'Guidance Appointment',
                recordType: 'GuidanceAppointment',
                recordId: $appointment->id,
                description: "Appointment #{$appointment->id} transitioned from {$previousStatus} to {$targetState}.",
                userId: $actor->id,
            );

            return $appointment->fresh();
        });

        // ── Notify only after the transaction commits ──
        $this->sendNotification($result, $targetState, $actor);

        return $result;
    }

    protected function validateCancellationNotice(GuidanceAppointment $appointment): void
    {
        $settings = GuidanceSchedulingSettings::getActive();
        $noticeMinutes = $settings->student_cancellation_notice_minutes;

        $startDateTime = $appointment->appointment_date->copy()
            ->setTimeFromTimeString($appointment->start_time);

        if (now()->addMinutes($noticeMinutes)->gt($startDateTime)) {
            throw new \InvalidArgumentException(
                "Cannot cancel less than {$noticeMinutes} minutes before the appointment."
            );
        }
    }

    protected function sendNotification(
        GuidanceAppointment $appointment,
        string $newStatus,
        User $actor
    ): void {
        $studentUser = $appointment->studentProfile?->user;
        $counselorUser = $appointment->counselor;

        $dateLabel = $appointment->appointment_date?->format('F j, Y') ?? 'the scheduled date';
        $timeLabel = $this->formatTimeRange($appointment);
        $whenLabel = trim("{$dateLabel} at {$timeLabel}");

        [$type, $recipient, $title, $message, $nextStep] = match ($newStatus) {
            GuidanceAppointment::STATUS_CONFIRMED => [
                NotificationType::GUIDANCE_APPOINTMENT_CONFIRMED,
                $studentUser,
                'Appointment Confirmed',
                "Your guidance appointment on {$whenLabel} has been confirmed by the counselor.",
                'Please arrive on time and bring any requirements. Log in for complete details.',
            ],
            GuidanceAppointment::STATUS_REJECTED => [
                NotificationType::GUIDANCE_APPOINTMENT_REJECTED,
                $studentUser,
                'Appointment Rejected',
                "Your guidance appointment request on {$whenLabel} was not approved. Please check the details.",
                'You may book another available schedule from your dashboard.',
            ],
            GuidanceAppointment::STATUS_CANCELLED => [
                NotificationType::GUIDANCE_APPOINTMENT_CANCELLED,
                $actor->isStudent() ? $counselorUser : $studentUser,
                'Appointment Cancelled',
                "A guidance appointment on {$whenLabel} has been cancelled.",
                'No further action is required. Log in for complete details.',
            ],
            GuidanceAppointment::STATUS_COMPLETED => [
                NotificationType::GUIDANCE_APPOINTMENT_COMPLETED,
                $studentUser,
                'Appointment Completed',
                "Your guidance appointment on {$whenLabel} has been marked as completed.",
                'No further action is required. Log in for complete details.',
            ],
            default => [null, null, null, null, null],
        };

        if (! $type || ! $recipient) {
            return;
        }

        // Deliberately exclude counseling notes and other confidential case details.
        $url = $recipient->isCounselor()
            ? route('counselor.guidance.appointments.show', $appointment->id)
            : route('student.guidance.appointments.show', $appointment->id);

        $this->notificationService->notifyUser(
            $recipient,
            $type,
            $title,
            $message,
            'GuidanceAppointment',
            $appointment->id,
            [
                'reference' => 'APT-'.$appointment->id,
                'reference_label' => 'Appointment No.',
                'status' => ucfirst($newStatus),
                'date' => $dateLabel,
                'time' => $timeLabel,
                'next_step' => $nextStep,
                'url' => $url,
            ],
            sendEmail: true,
        );
    }

    /**
     * Human-readable appointment time range (e.g. "9:00 AM – 9:30 AM").
     */
    protected function formatTimeRange(GuidanceAppointment $appointment): string
    {
        $start = $appointment->start_time
            ? Carbon::parse($appointment->start_time)->format('g:i A')
            : null;
        $end = $appointment->end_time
            ? Carbon::parse($appointment->end_time)->format('g:i A')
            : null;

        return match (true) {
            $start && $end => "{$start} – {$end}",
            (bool) $start => $start,
            default => 'the scheduled time',
        };
    }
}
