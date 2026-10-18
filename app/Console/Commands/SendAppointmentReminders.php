<?php

namespace App\Console\Commands;

use App\Enums\NotificationType;
use App\Models\GuidanceAppointment;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Sends in-app and email reminders for upcoming confirmed guidance appointments.
 *
 * Idempotency: `reminder_sent_at` is stamped before notifying so re-runs (or
 * overlapping schedulers) never remind the same appointment twice.
 *
 * Run locally with:  php artisan guidance:send-reminders
 * Run continuously:  php artisan schedule:work
 */
class SendAppointmentReminders extends Command
{
    protected $signature = 'guidance:send-reminders {--hours=24 : Reminder window in hours}';

    protected $description = 'Send reminders for confirmed guidance appointments happening soon.';

    public function handle(NotificationService $notifications): int
    {
        $hours = max(1, (int) $this->option('hours'));
        $windowStart = now();
        $windowEnd = now()->addHours($hours);

        $appointments = GuidanceAppointment::with(['studentProfile.user'])
            ->where('status', GuidanceAppointment::STATUS_CONFIRMED)
            ->whereNull('reminder_sent_at')
            ->whereDate('appointment_date', '<=', $windowEnd->toDateString())
            ->get();

        $sent = 0;

        foreach ($appointments as $appointment) {
            $start = $appointment->appointment_date->copy()->setTimeFromTimeString($appointment->start_time);

            if ($start->lt($windowStart) || $start->gt($windowEnd)) {
                continue;
            }

            $studentUser = $appointment->studentProfile?->user;

            // Stamp first so a later failure cannot cause duplicate reminders.
            $appointment->update(['reminder_sent_at' => now()]);

            if (! $studentUser) {
                continue;
            }

            $dateLabel = $appointment->appointment_date->format('F j, Y');
            $timeLabel = $appointment->start_time
                ? Carbon::parse($appointment->start_time)->format('g:i A')
                : 'the scheduled time';

            $notifications->notifyUser(
                $studentUser,
                NotificationType::GUIDANCE_APPOINTMENT_REMINDER,
                'Appointment Reminder',
                "Reminder: you have a guidance appointment on {$dateLabel} at {$timeLabel}.",
                'GuidanceAppointment',
                $appointment->id,
                [
                    'reference' => 'APT-'.$appointment->id,
                    'reference_label' => 'Appointment No.',
                    'status' => 'Confirmed',
                    'date' => $dateLabel,
                    'time' => $timeLabel,
                    'next_step' => 'Please arrive on time. Log in for complete details.',
                    'url' => route('student.guidance.appointments.show', $appointment->id),
                ],
                sendEmail: true,
            );

            $sent++;
        }

        $this->info("Sent {$sent} guidance appointment reminder(s).");

        return self::SUCCESS;
    }
}
