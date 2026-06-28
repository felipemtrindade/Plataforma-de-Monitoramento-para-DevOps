<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoginFailure;
use App\Models\Metric;
use App\Models\SecurityEvent;
use App\Models\Service;
use App\Services\NetworkMonitor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class SecurityEventController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'events' => SecurityEvent::with('service')->latest()->limit(100)->get(),
            'known_vulnerabilities' => SecurityEvent::with('service')->where('type', 'VULNERABILITY')->latest()->get(),
            'login_failures' => LoginFailure::latest()->limit(50)->get(),
        ]);
    }

    public function simulateLoginFailure(Request $request): JsonResponse
    {
        $ip = $request->input('source_ip', $request->ip());
        $email = $request->input('email', 'admin@monitor.local');

        LoginFailure::create([
            'source_ip' => $ip,
            'email' => $email,
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ]);

        $key = "failed_login:{$ip}";
        $attempts = Cache::get($key, 0) + 1;
        Cache::put($key, $attempts, now()->addMinutes(10));

        $event = null;

        if ($attempts >= 5) {
            $event = SecurityEvent::create([
                'service_id' => null,
                'type' => 'BRUTE_FORCE',
                'level' => 'HIGH',
                'description' => "Cinco ou mais falhas de login detectadas para o IP {$ip}.",
                'source_ip' => $ip,
            ]);

            Cache::forget($key);
        }

        return response()->json([
            'source_ip' => $ip,
            'failed_attempts' => $event ? 5 : $attempts,
            'event' => $event,
        ], $event ? 201 : 200);
    }

    public function simulateTrafficAnomaly(Request $request, NetworkMonitor $monitor): JsonResponse
    {
        $data = $request->validate([
            'service_id' => ['nullable', 'exists:services,id'],
            'requests_per_second' => ['nullable', 'integer', 'min:1001'],
        ]);

        $service = isset($data['service_id'])
            ? Service::findOrFail($data['service_id'])
            : Service::query()->firstOrFail();

        $requestsPerSecond = $data['requests_per_second'] ?? 1500;

        Metric::create([
            'service_id' => $service->id,
            'status' => $service->current_status,
            'latency_ms' => 180,
            'requests_per_second' => $requestsPerSecond,
            'error_rate' => 1.5,
            'active_connections' => 700,
            'qps' => in_array($service->type, ['DATABASE', 'DNS'], true) ? 300 : 0,
            'dns_response_time' => $service->type === 'DNS' ? 80 : null,
            'smtp_queue_size' => $service->type === 'SMTP' ? 12 : 0,
        ]);

        return response()->json($monitor->recordTrafficAnomaly($service, $requestsPerSecond), 201);
    }

    public function simulateConfigChange(NetworkMonitor $monitor): JsonResponse
    {
        $path = storage_path('app/monitor_config.txt');
        File::ensureDirectoryExists(dirname($path));
        $current = File::exists($path) ? File::get($path) : '';
        File::put($path, $current."\nchanged_at=".now()->toDateTimeString());

        return response()->json([
            'message' => 'Arquivo de configuração alterado.',
            'event' => $monitor->checkConfigChange(),
        ], 201);
    }
}
