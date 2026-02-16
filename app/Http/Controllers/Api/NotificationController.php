<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->user()->notifications();

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('is_read')) {
            $query->where('is_read', $request->boolean('is_read'));
        }

        $notifications = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $notifications
        ]);
    }

    public function markAsRead(Request $request, $id)
    {
        $notification = Notification::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read',
            'data' => $notification
        ]);
    }

    public function markAllAsRead(Request $request)
    {
        $request->user()->notifications()
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read'
        ]);
    }

    public function unreadCount(Request $request)
    {
        $count = $request->user()->notifications()->unread()->count();

        return response()->json([
            'success' => true,
            'data' => [
                'unread_count' => $count
            ]
        ]);
    }
}