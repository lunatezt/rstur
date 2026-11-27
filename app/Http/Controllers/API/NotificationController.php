<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->admin($request);

        return response()->json([
            'notifications' => $user->notifications()
                ->latest()
                ->limit(50)
                ->get()
                ->map(fn ($notification) => [
                    'id' => $notification->id,
                    'data' => $notification->data,
                    'read_at' => $notification->read_at?->toISOString(),
                    'created_at' => $notification->created_at?->toISOString(),
                ]),
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    public function markRead(Request $request, string $id)
    {
        $user = $this->admin($request);
        $notification = $user->notifications()->whereKey($id)->firstOrFail();
        $notification->markAsRead();

        return response()->noContent();
    }

    public function markAllRead(Request $request)
    {
        $user = $this->admin($request);
        $user->unreadNotifications()->update(['read_at' => now()]);

        return response()->noContent();
    }

    private function admin(Request $request): User
    {
        $user = $request->user();
        abort_unless($user->role?->slug === 'admin', 403);

        return $user;
    }
}
