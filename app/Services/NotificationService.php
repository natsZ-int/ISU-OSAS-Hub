<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Jobs\SendNotificationEmail;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Create a notification for a single user.
     *
     * Prevents duplicates by checking for an existing unread notification
     * with the same type + related_type + related_id for the same user.
     *
     * Callers must invoke this AFTER the underlying business transaction has
     * committed so a rolled-back change never produces a notification or email.
     *
     * @param  array<string, mixed>  $data  Non-confidential metadata (reference, status, next_step, url, reason).
     * @param  bool  $sendEmail  Queue an email for this notification.
     */
    public function notifyUser(
        User $recipient,
        NotificationType $type,
        string $title,
        string $message,
        ?string $relatedType = null,
        ?int $relatedId = null,
        array $data = [],
        bool $sendEmail = false,
    ): ?Notification {
        if (! $recipient->isActive()) {
            return null;
        }

        // Duplicate prevention: skip if an identical unread notification already exists
        if ($relatedType && $relatedId) {
            $exists = Notification::where('user_id', $recipient->id)
                ->where('type', $type->value)
                ->where('related_type', $relatedType)
                ->where('related_id', $relatedId)
                ->unread()
                ->exists();

            if ($exists) {
                return null;
            }
        }

        try {
            $notification = Notification::create([
                'user_id' => $recipient->id,
                'type' => $type->value,
                'title' => $title,
                'message' => $message,
                'related_type' => $relatedType,
                'related_id' => $relatedId,
                'data' => $data ?: null,
            ]);
        } catch (\Throwable $e) {
            Log::warning("Notification creation failed: {$e->getMessage()}");

            return null;
        }

        // Only queue the email once the in-app record exists, so the two channels
        // never disagree and duplicate events never send a second email.
        if ($sendEmail) {
            $this->queueEmail($notification);
        }

        return $notification;
    }

    /**
     * Notify multiple users (e.g., all active admins).
     *
     * @param  User[]  $recipients
     * @param  array<string, mixed>  $data
     */
    public function notifyMultiple(
        array $recipients,
        NotificationType $type,
        string $title,
        string $message,
        ?string $relatedType = null,
        ?int $relatedId = null,
        array $data = [],
        bool $sendEmail = false,
    ): void {
        foreach ($recipients as $recipient) {
            $this->notifyUser($recipient, $type, $title, $message, $relatedType, $relatedId, $data, $sendEmail);
        }
    }

    /**
     * Queue the outbound email for a persisted notification.
     *
     * Failures are swallowed (and logged) so a queueing problem never breaks the
     * business action; the in-app notification remains the source of truth.
     */
    protected function queueEmail(Notification $notification): void
    {
        try {
            SendNotificationEmail::dispatch($notification->id);
        } catch (\Throwable $e) {
            Log::warning("Notification email queueing failed: {$e->getMessage()}");
        }
    }

    /**
     * Get the unread notification count for a user.
     */
    public function getUnreadCount(User $user): int
    {
        return Notification::forUser($user->id)->unread()->count();
    }

    /**
     * Mark a single notification as read (ownership verified externally).
     */
    public function markAsRead(Notification $notification): void
    {
        $notification->markAsRead();
    }

    /**
     * Mark all unread notifications for a user as read.
     */
    public function markAllAsRead(User $user): int
    {
        return Notification::forUser($user->id)
            ->unread()
            ->update(['read_at' => now()]);
    }
}
