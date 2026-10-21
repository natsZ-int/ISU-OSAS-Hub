<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Models\CounselorAvailability;
use App\Models\GuidanceAppointment;
use App\Models\GuidanceAppointmentType;
use App\Models\GuidanceSchedulingSettings;
use App\Models\StudentProfile;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GuidanceAppointmentSchedulingService
{
    public function __construct(
        protected GuidanceAppointmentWorkflowService $workflowService,
        protected NotificationService $notificationService,
    ) {}

    // ── Get Available Slots ─────────────────────────

    // Generate all valid appointment slots for a counselor on a given date
    public function getAvailableSlots(
        User $counselor,
        CarbonInterface $date,
        ?GuidanceAppointmentType $appointmentType = null
    ): Collection {
        $settings = GuidanceSchedulingSettings::getActive();

        // Check date is within allowed booking range
        if (! $this->isDateBookable($date, $settings)) {
            return collect();
        }

        // Determine duration
        $duration = $this->getDuration($appointmentType, $settings);

        // Get counselor availability for this day of week
        $dayOfWeek = (int) $date->dayOfWeek;
        $availabilities = CounselorAvailability::where('counselor_id', $counselor->id)
            ->forDay($dayOfWeek)
            ->active()
            ->get();

        if ($availabilities->isEmpty()) {
            return collect();
        }

        // Generate candidate slots from availability windows
        $candidates = $this->generateCandidateSlots($availabilities, $duration, $settings, $date);

        // Get blocking appointments for this counselor on this date
        $counselorAppointments = GuidanceAppointment::counselorBlockingOnDate(
            $counselor->id, $date->toDateString()
        )->get();

        // Filter out conflicting slots
        return $candidates->filter(function ($slot) use ($counselorAppointments) {
            foreach ($counselorAppointments as $appt) {
                if ($this->timesOverlap($appt->start_time, $appt->end_time, $slot['start'], $slot['end'])) {
                    return false;
                }
            }

            return true;
        })->values();
    }

    // ── Book Appointment ────────────────────────────

    // Create an appointment with full conflict checking inside a transaction
    public function bookAppointment(
        StudentProfile $studentProfile,
        User $counselor,
        GuidanceAppointmentType $appointmentType,
        CarbonInterface $date,
        string $startTime,
        ?string $reason,
        User $actor
    ): GuidanceAppointment {
        $settings = GuidanceSchedulingSettings::getActive();
        $duration = $this->getDuration($appointmentType, $settings);
        $endTime = Carbon::parse($startTime)->addMinutes($duration)->format('H:i:s');

        $appointment = DB::transaction(function () use (
            $studentProfile, $counselor, $appointmentType,
            $date, $startTime, $endTime, $reason, $actor, $settings
        ) {
            // Re-validate everything inside the transaction

            // 1. Date must be bookable
            if (! $this->isDateBookable($date, $settings)) {
                throw new \InvalidArgumentException('The selected date is not available for booking.');
            }

            // 2. Time must be within office hours
            if (! $this->isWithinOfficeHours($startTime, $endTime, $settings)) {
                throw new \InvalidArgumentException('The selected time is outside office hours.');
            }

            // 3. Minimum booking notice
            $startDateTime = $date->copy()->setTimeFromTimeString($startTime);
            if (now()->addMinutes($settings->minimum_booking_notice_minutes)->gt($startDateTime)) {
                throw new \InvalidArgumentException('The selected time is too soon. Minimum booking notice required.');
            }

            // 4. Counselor must be available for the full slot
            if (! $this->isCounselorAvailable($counselor, $date, $startTime, $endTime)) {
                throw new \InvalidArgumentException('The counselor is not available for the selected time.');
            }

            // 5. No counselor conflict
            if ($this->hasCounselorConflict($counselor, $date, $startTime, $endTime)) {
                throw new \InvalidArgumentException('The counselor has a conflicting appointment at this time.');
            }

            // 6. No student conflict
            if ($this->hasStudentConflict($studentProfile, $date, $startTime, $endTime)) {
                throw new \InvalidArgumentException('You have a conflicting appointment at this time.');
            }

            // 7. Create the appointment
            $appointment = GuidanceAppointment::create([
                'student_profile_id' => $studentProfile->id,
                'counselor_id' => $counselor->id,
                'appointment_type_id' => $appointmentType->id,
                'appointment_type' => $appointmentType->name,
                'appointment_date' => $date->toDateString(),
                'start_time' => $startTime,
                'end_time' => $endTime,
                'reason' => $reason,
                'status' => GuidanceAppointment::STATUS_REQUESTED,
            ]);

            // 8. Audit log
            AuditLogService::log(
                action: 'Appointment Requested',
                module: 'Guidance Appointment',
                recordType: 'GuidanceAppointment',
                recordId: $appointment->id,
                description: "Student requested appointment with counselor on {$date->toDateString()} at {$startTime}.",
                userId: $actor->id,
            );

            return $appointment;
        });

        // Notify the counselor only after the transaction commits. This is an
        // in-app notification; the required email events are confirmation,
        // rescheduling, cancellation and reminders handled by the workflow.
        $this->notificationService->notifyUser(
            $counselor,
            NotificationType::GUIDANCE_APPOINTMENT_REQUESTED,
            'New Appointment Request',
            "A student has requested an appointment on {$date->format('M d, Y')} at {$startTime}.",
            'GuidanceAppointment',
            $appointment->id,
            [
                'reference' => 'APT-'.$appointment->id,
                'reference_label' => 'Appointment No.',
                'status' => 'Requested',
                'next_step' => 'Review and confirm or reject the appointment request.',
                'url' => route('counselor.guidance.appointments.show', $appointment->id),
            ],
            sendEmail: false,
        );

        return $appointment;
    }

    // ── Conflict Checks ─────────────────────────────

    // Check if counselor is available for the full time slot
    public function isCounselorAvailable(
        User $counselor,
        CarbonInterface $date,
        string $startTime,
        string $endTime
    ): bool {
        $dayOfWeek = (int) $date->dayOfWeek;

        $availabilities = CounselorAvailability::where('counselor_id', $counselor->id)
            ->forDay($dayOfWeek)
            ->active()
            ->get();

        // Check if any availability window fully covers the requested slot
        foreach ($availabilities as $avail) {
            if ($avail->start_time <= $startTime && $avail->end_time >= $endTime) {
                return true;
            }
        }

        return false;
    }

    // Check if counselor has a conflicting appointment
    public function hasCounselorConflict(
        User $counselor,
        CarbonInterface $date,
        string $startTime,
        string $endTime
    ): bool {
        return GuidanceAppointment::counselorBlockingOnDate($counselor->id, $date->toDateString())
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime);
            })
            ->exists();
    }

    // Check if student has a conflicting appointment
    public function hasStudentConflict(
        StudentProfile $studentProfile,
        CarbonInterface $date,
        string $startTime,
        string $endTime
    ): bool {
        return GuidanceAppointment::studentBlockingOnDate($studentProfile->id, $date->toDateString())
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime);
            })
            ->exists();
    }

    // ── Overlap Formula ─────────────────────────────

    // Half-open interval overlap: [startA, endA) and [startB, endB)
    // Overlap exists when: startA < endB AND endA > startB
    protected function timesOverlap(
        string $startA, string $endA,
        string $startB, string $endB
    ): bool {
        return $startA < $endB && $endA > $startB;
    }

    // ── Slot Generation ─────────────────────────────

    // Divide availability windows into fixed-duration slots
    protected function generateCandidateSlots(
        Collection $availabilities,
        int $durationMinutes,
        GuidanceSchedulingSettings $settings,
        CarbonInterface $date
    ): Collection {
        $slots = collect();
        $officeStart = $settings->office_start_time;
        $officeEnd = $settings->office_end_time;
        $now = now();
        $noticeMinutes = $settings->minimum_booking_notice_minutes;

        foreach ($availabilities as $avail) {
            // Restrict to office hours
            $windowStart = max($avail->start_time, $officeStart);
            $windowEnd = min($avail->end_time, $officeEnd);

            $current = Carbon::parse($date->toDateString().' '.$windowStart);
            $end = Carbon::parse($date->toDateString().' '.$windowEnd);

            while ($current->copy()->addMinutes($durationMinutes)->lte($end)) {
                $slotStart = $current->format('H:i:s');
                $slotEnd = $current->copy()->addMinutes($durationMinutes)->format('H:i:s');

                // Skip slots in the past or within the notice period
                $slotDateTime = $date->copy()->setTimeFromTimeString($slotStart);
                if ($slotDateTime->lte($now->copy()->addMinutes($noticeMinutes))) {
                    $current->addMinutes($durationMinutes);

                    continue;
                }

                $slots->push([
                    'start' => $slotStart,
                    'end' => $slotEnd,
                    'display' => Carbon::parse($slotStart)->format('g:i A').' – '.Carbon::parse($slotEnd)->format('g:i A'),
                ]);

                $current->addMinutes($durationMinutes);
            }
        }

        return $slots;
    }

    // ── Helpers ─────────────────────────────────────

    // Check if a date is within the allowed booking range
    protected function isDateBookable(CarbonInterface $date, GuidanceSchedulingSettings $settings): bool
    {
        $today = now()->startOfDay();
        $maxDate = $today->copy()->addDays($settings->maximum_future_booking_days);

        return $date->gte($today) && $date->lte($maxDate);
    }

    // Check if a time range is within office hours
    protected function isWithinOfficeHours(
        string $startTime,
        string $endTime,
        GuidanceSchedulingSettings $settings
    ): bool {
        return $startTime >= $settings->office_start_time
            && $endTime <= $settings->office_end_time;
    }

    // Get appointment duration in minutes
    protected function getDuration(
        ?GuidanceAppointmentType $type,
        GuidanceSchedulingSettings $settings
    ): int {
        if ($type && $type->duration_minutes) {
            return $type->duration_minutes;
        }

        return $settings->default_appointment_duration_minutes;
    }
}
