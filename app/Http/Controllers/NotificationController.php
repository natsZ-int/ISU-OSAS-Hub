<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class NotificationController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function index(Request $request)
    {
        $user = auth()->user();

        $notifications = Notification::forUser($user->id)
            ->latest('created_at')
            ->paginate(15);

        $unreadCount = $this->notificationService->getUnreadCount($user);

        return view('notifications.index', compact('notifications', 'unreadCount'));
    }

    public function markAsRead(Notification $notification)
    {
        // Ownership check: user can only mark their own notifications
        if ($notification->user_id !== auth()->id()) {
            abort(403);
        }

        $this->notificationService->markAsRead($notification);

        return redirect()->back()->with('status', 'Notification marked as read.');
    }

    public function markAllAsRead()
    {
        $count = $this->notificationService->markAllAsRead(auth()->user());

        return redirect()->route('notifications.index')
            ->with('status', "{$count} notification(s) marked as read.");
    }
}