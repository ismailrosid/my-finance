<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Poll endpoint for real-time frontend notification checking.
     */
    public function poll(Request $request): JsonResponse
    {
        $user = $request->user();

        // Detect financial events & generate notifications automatically
        $this->notificationService->checkAndGenerateNotifications($user);

        $unreadCount = $this->notificationService->getUnreadCount($user);
        $notifications = $this->notificationService->getRecentNotifications($user, 20);

        $mapped = $notifications->map(function ($n) {
            return [
                'id' => $n->id,
                'type' => $n->type,
                'title' => $n->title,
                'message' => $n->message,
                'variant' => $n->variant,
                'action_url' => $n->action_url,
                'is_read' => $n->is_read,
                'time_ago' => $n->created_at ? $n->created_at->diffForHumans() : 'Just now',
            ];
        });

        return response()->json([
            'status' => 'success',
            'unread_count' => $unreadCount,
            'poll_interval' => (int) config('myfinance.notification_poll_interval', 15),
            'notifications' => $mapped,
        ]);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $success = $this->notificationService->markAsRead($user, $id);

        return response()->json([
            'status' => $success ? 'success' : 'error',
            'unread_count' => $this->notificationService->getUnreadCount($user),
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();
        $updated = $this->notificationService->markAllAsRead($user);

        return response()->json([
            'status' => 'success',
            'updated' => $updated,
            'unread_count' => 0,
        ]);
    }
}

