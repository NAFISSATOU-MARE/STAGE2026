<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // ─── GET /api/notifications ───────────────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $notifications = Notification::where('agent_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return response()->json([
            'notifications' => $notifications,
            'non_lues'      => $notifications->where('lu', false)->count(),
        ]);
    }

    // ─── PUT /api/notifications/{id}/lire ────────────────────────────────
    public function lire(Request $request, Notification $notification): JsonResponse
    {
        if ($notification->agent_id !== $request->user()->id) {
            abort(403);
        }
        $notification->update(['lu' => true]);
        return response()->json(['ok' => true]);
    }

    // ─── PUT /api/notifications/lire-tout ────────────────────────────────
    public function lireTout(Request $request): JsonResponse
    {
        Notification::where('agent_id', $request->user()->id)
            ->where('lu', false)
            ->update(['lu' => true]);

        return response()->json(['ok' => true]);
    }
}
