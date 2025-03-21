<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        try {
            return response()->json([
                'notifications' => $request->user()->notifications()->latest()->take(5)->get(),
                'unreadCount' => $request->user()->unreadNotifications()->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch notifications'], 500);
        }
    }

    public function markAsRead(Request $request, $id)
    {
        try {
            $notification = $request->user()->notifications()->findOrFail($id);
            $notification->markAsRead();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to mark notification as read'], 500);
        }
    }

    public function markAllAsRead(Request $request)
    {
        try {
            $request->user()->unreadNotifications()->update(['read_at' => now()]);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to mark notifications as read'], 500);
        }
    }
}
