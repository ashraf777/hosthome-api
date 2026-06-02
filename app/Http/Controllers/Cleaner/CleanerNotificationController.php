<?php

namespace App\Http\Controllers\Cleaner;

use App\Http\Controllers\Controller;
use App\Models\UserNotification;
use Illuminate\Http\Request;

class CleanerNotificationController extends Controller
{
    /**
     * Get paginated notifications for the authenticated cleaner.
     *
     * GET /api/cleaner/notifications
     */
    public function index(Request $request)
    {
        $notifications = UserNotification::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(30);

        return response()->json([
            'data' => $notifications->items(),
            'meta' => [
                'total'        => $notifications->total(),
                'current_page' => $notifications->currentPage(),
                'last_page'    => $notifications->lastPage(),
                'unread_count' => UserNotification::where('user_id', $request->user()->id)
                    ->where('is_read', false)
                    ->count(),
            ],
        ]);
    }

    /**
     * Get unread notification count (for badge display).
     *
     * GET /api/cleaner/notifications/unread-count
     */
    public function unreadCount(Request $request)
    {
        $count = UserNotification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->count();

        return response()->json(['unread_count' => $count]);
    }

    /**
     * Mark a single notification as read.
     *
     * PUT /api/cleaner/notifications/{id}/read
     */
    public function markRead(Request $request, int $id)
    {
        $notification = UserNotification::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$notification) {
            return response()->json(['message' => 'Notification not found.'], 404);
        }

        $notification->update(['is_read' => true]);

        return response()->json(['message' => 'Notification marked as read.']);
    }

    /**
     * Mark all notifications as read.
     *
     * PUT /api/cleaner/notifications/read-all
     */
    public function markAllRead(Request $request)
    {
        UserNotification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['message' => 'All notifications marked as read.']);
    }
}
