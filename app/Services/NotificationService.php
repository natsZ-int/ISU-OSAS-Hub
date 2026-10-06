<?php

namespace App\Services;

use App\Enums\NotificationType;
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
     */
    public function notifyUser(
        User $recipient,
        NotificationType $type,
        string $title,
        string $message,
        ?string $relatedType = null,
        ?int $relatedId = null,
    ): ?Notification {
        if (!$recipient->isActive()) {
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
            return Notification::create([
                'user_id'      => $recipient->id,
                'type'         => $type->value,
                'title'        => $title,
                'message'      => $message,
                'related_type' => $relatedType,
                'related_id'   => $relatedId,
            ]);
        } catch (\Throwable $e) {
            Log::warning("Notification creation failed: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Notify multiple users (e.g., all active admins).
     *
     * @param  User[]  $recipients
     */
    public function notifyMultiple(
        array $recipients,
        NotificationType $type,
        string $title,
        string $message,
        ?string $relatedType = null,
        ?int $relatedId = null,
    ): void {
        foreach ($recipients as $recipient) {
            $this->notifyUser($recipient, $type, $title, $message, $relatedType, $relatedId);
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