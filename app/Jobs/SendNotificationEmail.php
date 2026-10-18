<?php

namespace App\Jobs;

use App\Mail\NotificationMail;
use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Delivers a single persisted notification as an email.
 *
 * The job is queued so SMTP latency never blocks the student/staff request.
 * Idempotency is enforced by claiming the notification's `emailed_at` column
 * before sending, so retries (or duplicate dispatch) never email twice.
 * A failed delivery un-claims the row and is retried with backoff; the
 * underlying business data is never touched.
 */
class SendNotificationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public int $notificationId) {}

    public function handle(): void
    {
        $notification = Notification::with('user')->find($this->notificationId);

        if (! $notification) {
            return;
        }

        $user = $notification->user;

        if (! $user || blank($user->email)) {
            Log::info('Notification email skipped: recipient has no email address.', [
                'notification_id' => $notification->id,
            ]);

            return;
        }

        // Atomically claim the notification so concurrent workers or retries
        // cannot deliver the same email twice.
        $claimed = Notification::whereKey($notification->id)
            ->whereNull('emailed_at')
            ->update(['emailed_at' => now()]);

        if ($claimed === 0) {
            return;
        }

        try {
            Mail::to($user->email)->send(
                NotificationMail::fromNotification($notification, $user->name)
            );
        } catch (\Throwable $e) {
            // Release the claim so the queue can retry a genuine failure.
            Notification::whereKey($notification->id)->update(['emailed_at' => null]);

            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::warning('Notification email delivery failed.', [
            'notification_id' => $this->notificationId,
            'error' => $e->getMessage(),
        ]);
    }
}
