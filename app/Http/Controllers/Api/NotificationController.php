<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected NotificationService $notifications;

    public function __construct(NotificationService $notifications)
    {
        $this->notifications = $notifications;
    }

    /**
     * Daftar notifikasi milik pengguna yang login.
     * Query: ?filter=unread untuk hanya yang belum dibaca, ?per_page=15.
     */
    public function index(Request $request)
    {
        $request->validate([
            'filter' => 'nullable|in:all,unread',
            'per_page' => 'nullable|integer|min:1|max:50',
        ]);

        $query = $request->user()->notifications()->latest();

        if ($request->filter === 'unread') {
            $query->whereNull('read_at');
        }

        $paginated = $query->paginate($request->integer('per_page') ?: 15);

        return response()->json([
            'data' => $paginated->items(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
            'unread_count' => $this->notifications->unreadCount($request->user()),
        ]);
    }

    public function markAsRead(Request $request, $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);

        if (!$notification->read_at) {
            $notification->markAsRead();
        }

        return response()->json([
            'notification' => $notification->fresh(),
            'unread_count' => $this->notifications->unreadCount($request->user()),
        ]);
    }

    public function markAllRead(Request $request)
    {
        $updated = $request->user()->notifications()->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json([
            'message' => 'Semua notifikasi ditandai sudah dibaca',
            'updated' => $updated,
            'unread_count' => 0,
        ]);
    }

    public function unreadCount(Request $request)
    {
        return response()->json(['count' => $this->notifications->unreadCount($request->user())]);
    }

    public function destroy(Request $request, $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->delete();

        return response()->json([
            'message' => 'Notifikasi dihapus',
            'unread_count' => $this->notifications->unreadCount($request->user()),
        ]);
    }

    public function destroyAll(Request $request)
    {
        $deleted = $request->user()->notifications()->delete();

        return response()->json([
            'message' => 'Semua notifikasi dihapus',
            'deleted' => $deleted,
            'unread_count' => 0,
        ]);
    }
}
