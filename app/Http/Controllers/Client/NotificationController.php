<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    /**
     * List the authenticated customer's notifications.
     */
    public function index(Request $request): Response
    {
        $notifications = $request->user()->notifications()->paginate(15);

        return Inertia::render('client/notifications', [
            'notifications' => [
                'data' => $notifications->getCollection()->map(fn (DatabaseNotification $n): array => [
                    'id' => $n->id,
                    'title' => $n->data['title'] ?? 'Notification',
                    'message' => $n->data['message'] ?? '',
                    'booking_id' => $n->data['booking_id'] ?? null,
                    'session_id' => $n->data['session_id'] ?? null,
                    'read_at' => $n->read_at?->toIso8601String(),
                    'created_at' => $n->created_at?->toIso8601String(),
                ])->all(),
                'meta' => [
                    'current_page' => $notifications->currentPage(),
                    'last_page' => $notifications->lastPage(),
                    'from' => $notifications->firstItem(),
                    'to' => $notifications->lastItem(),
                    'total' => $notifications->total(),
                    'links' => $notifications->linkCollection()->toArray(),
                    'path' => $notifications->path(),
                    'per_page' => $notifications->perPage(),
                ],
            ],
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    /**
     * Mark a single notification as read (owner only).
     */
    public function markAsRead(Request $request, string $notification): RedirectResponse
    {
        // Scoping to the user's own notifications enforces ownership.
        $request->user()->notifications()->findOrFail($notification)->markAsRead();

        return back();
    }

    /**
     * Mark all of the customer's notifications as read.
     */
    public function markAllAsRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }
}
