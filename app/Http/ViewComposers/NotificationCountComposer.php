<?php

namespace App\Http\ViewComposers;

use App\Services\NotificationService;
use Illuminate\View\View;

class NotificationCountComposer
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function compose(View $view): void
    {
        $user = auth()->user();

        $view->with('unreadNotificationCount', $user ? $this->notificationService->getUnreadCount($user) : 0);
    }
}
