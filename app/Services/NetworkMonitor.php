<?php

namespace App\Services;

use App\Models\Metric;
use App\Models\SecurityEvent;
use App\Models\Service;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class NetworkMonitor
{
    public function __construct(private readonly AlertService $alerts)
    {
    }

    public function check(Service $service): Metric
    {
        $startedAt = microtime(true);
        $probe = match ($service->type) {
            'WEB' => $this->checkWeb($service),
            'DATABASE' => $this->checkDatabase($service),
            'DNS' => $this->checkDns($service),
            'SMTP' => $this->checkTcp($service),
            default => ['up' => false],
        };

        $isUp = $probe['up'];
        $latency = (int) round((microtime(true) - $startedAt) * 1000);
        $service->update(['current_status' => $isUp ? 'UP' : 'DOWN']);
        $errorRate = $isUp ? random_int(0, 8) / 10 : random_int(20, 80) / 10;

        $metric = Metric::create([
            'service_id' => $service->id,
            'status' => $isUp ? 'UP' : 'DOWN',
            'latency_ms' => $isUp ? max($latency, 1) : null,
            'http_status_code' => $probe['http_status_code'] ?? null,
            'requests_per_second' => random_int(80, 950),
            'error_rate' => $errorRate,
            'error_count' => $this->errorCountFromRate($errorRate),
            'active_connections' => random_int(10, 300),
            'qps' => in_array($service->type, ['DATABASE', 'DNS'], true) ? random_int(20, 250) : 0,
            'cpu_usage' => $service->type === 'DATABASE' ? random_int(2000, 8500) / 100 : null,
            'memory_usage' => $service->type === 'DATABASE' ? random_int(3000, 9000) / 100 : null,
            'io_wait' => $service->type === 'DATABASE' ? random_int(50, 1200) / 100 : null,
            'db_size_mb' => $service->type === 'DATABASE' ? random_int(512, 8192) : null,
            'slow_queries' => $service->type === 'DATABASE' ? random_int(0, 12) : 0,
            'dns_response_time' => $service->type === 'DNS' && $isUp ? max($latency, 1) : null,
            'failed_resolutions' => $service->type === 'DNS' && ! $isUp ? random_int(1, 10) : 0,
            'smtp_queue_size' => $service->type === 'SMTP' ? random_int(0, 50) : 0,
            'smtp_delivery_rate' => $service->type === 'SMTP' ? ($isUp ? random_int(9200, 9990) / 100 : random_int(5000, 8500) / 100) : null,
            'email_volume' => $service->type === 'SMTP' ? random_int(20, 600) : 0,
        ]);

        if ($metric->requests_per_second > 1000) {
            $this->recordTrafficAnomaly($service, $metric->requests_per_second);
        }

        $this->alerts->evaluate($service, $metric);

        return $metric;
    }

    public function checkConfigChange(): ?SecurityEvent
    {
        $path = storage_path('app/monitor_config.txt');

        if (! File::exists($path)) {
            File::ensureDirectoryExists(dirname($path));
            File::put($path, "monitoring_interval=60\nalert_email=devops@example.com\n");
        }

        $hash = hash('sha256', File::get($path));
        $previousHash = Cache::get('monitor_config_hash');
        Cache::forever('monitor_config_hash', $hash);

        if ($previousHash !== null && $previousHash !== $hash) {
            return SecurityEvent::create([
                'service_id' => null,
                'type' => 'CONFIG_CHANGE',
                'level' => 'HIGH',
                'description' => 'Alteração detectada no arquivo storage/app/monitor_config.txt.',
                'source_ip' => request()?->ip(),
            ]);
        }

        return null;
    }

    public function recordTrafficAnomaly(Service $service, int $requestsPerSecond): SecurityEvent
    {
        return SecurityEvent::create([
            'service_id' => $service->id,
            'type' => 'TRAFFIC_ANOMALY',
            'level' => 'HIGH',
            'description' => "Anomalia de tráfego no serviço {$service->name}: {$requestsPerSecond} req/s.",
            'source_ip' => request()?->ip(),
        ]);
    }

    private function checkWeb(Service $service): array
    {
        $url = str_starts_with($service->host, 'http')
            ? $service->host
            : "https://{$service->host}";

        try {
            $response = Http::timeout(5)->get($url);

            return [
                'up' => $response->successful(),
                'http_status_code' => $response->status(),
            ];
        } catch (\Throwable) {
            return [
                'up' => false,
                'http_status_code' => null,
            ];
        }
    }

    private function checkDatabase(Service $service): array
    {
        try {
            if ($service->host === config('database.connections.mysql.host') || in_array($service->host, ['127.0.0.1', 'localhost'], true)) {
                DB::connection()->getPdo();

                return ['up' => true];
            }

            return $this->checkTcp($service);
        } catch (\Throwable) {
            return ['up' => false];
        }
    }

    private function checkDns(Service $service): array
    {
        return ['up' => gethostbyname($service->host) !== $service->host];
    }

    private function checkTcp(Service $service): array
    {
        $connection = @fsockopen($service->host, (int) $service->port, $errno, $errstr, 5);

        if ($connection === false) {
            return ['up' => false];
        }

        fclose($connection);

        return ['up' => true];
    }

    private function errorCountFromRate(float $errorRate): int
    {
        return (int) round($errorRate * random_int(10, 40));
    }
}
