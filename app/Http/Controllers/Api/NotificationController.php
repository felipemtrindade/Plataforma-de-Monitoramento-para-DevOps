<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\SecurityEvent;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    public function index(): JsonResponse
    {
        $alerts = Alert::with('service')
            ->whereIn('level', ['YELLOW', 'RED'])
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (Alert $alert) => [
                'id' => "alert-{$alert->id}",
                'type' => 'ALERT',
                'level' => $alert->level,
                'title' => $alert->title,
                'message' => $alert->message,
                'created_at' => $alert->created_at,
                'service' => $alert->service?->name,
            ]);

        $events = SecurityEvent::with('service')
            ->whereIn('level', ['HIGH', 'CRITICAL'])
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (SecurityEvent $event) => [
                'id' => "security-{$event->id}",
                'type' => 'SECURITY',
                'level' => $event->level,
                'title' => $event->type,
                'message' => $event->description,
                'created_at' => $event->created_at,
                'service' => $event->service?->name,
            ]);

        return response()->json(
            $alerts->merge($events)->sortByDesc('created_at')->values()->take(12)
        );
    }
}
