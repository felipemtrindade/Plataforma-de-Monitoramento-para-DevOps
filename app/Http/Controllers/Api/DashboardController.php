<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\Metric;
use App\Models\SecurityEvent;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $latestMetrics = Metric::query()
            ->select('metrics.*')
            ->join(DB::raw('(select service_id, max(id) as id from metrics group by service_id) latest'), 'metrics.id', '=', 'latest.id')
            ->with('service')
            ->orderBy('metrics.created_at')
            ->get();

        return response()->json([
            'summary' => [
                'total_services' => Service::count(),
                'services_up' => Service::where('current_status', 'UP')->count(),
                'services_down' => Service::where('current_status', 'DOWN')->count(),
                'yellow_alerts' => Alert::where('level', 'YELLOW')->count(),
                'red_alerts' => Alert::where('level', 'RED')->count(),
                'security_events' => SecurityEvent::count(),
                'known_vulnerabilities' => SecurityEvent::where('type', 'VULNERABILITY')->count(),
                'average_error_rate' => round((float) Metric::avg('error_rate'), 2),
            ],
            'latency_by_service' => $latestMetrics->map(fn (Metric $metric) => [
                'service' => $metric->service->name,
                'latency_ms' => $metric->latency_ms ?? 0,
            ])->values(),
            'availability' => [
                'UP' => Service::where('current_status', 'UP')->count(),
                'DOWN' => Service::where('current_status', 'DOWN')->count(),
            ],
            'alerts_by_level' => Alert::query()
                ->select('level', DB::raw('count(*) as total'))
                ->groupBy('level')
                ->pluck('total', 'level'),
            'security_events_by_type' => SecurityEvent::query()
                ->select('type', DB::raw('count(*) as total'))
                ->groupBy('type')
                ->pluck('total', 'type'),
            'traffic_by_service' => $latestMetrics->map(fn (Metric $metric) => [
                'service' => $metric->service->name,
                'requests_per_second' => $metric->requests_per_second,
                'qps' => $metric->qps,
                'email_volume' => $metric->email_volume,
            ])->values(),
            'error_rate_by_service' => $latestMetrics->map(fn (Metric $metric) => [
                'service' => $metric->service->name,
                'error_rate' => (float) $metric->error_rate,
                'error_count' => $metric->error_count,
            ])->values(),
            'recent_alerts' => Alert::with('service')->latest()->limit(5)->get(),
            'recent_security_events' => SecurityEvent::latest()->limit(5)->get(),
        ]);
    }
}
