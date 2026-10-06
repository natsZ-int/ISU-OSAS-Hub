<?php

namespace App\Policies;

use App\Models\Notification;
use App\Models\User;

class NotificationPolicy
{
    public function view(User $user, Notification $notification): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $notification->user_id === $user->id;
    }
}