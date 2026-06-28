<?php

namespace Database\Seeders;

use App\Models\Alert;
use App\Models\LoginFailure;
use App\Models\Metric;
use App\Models\SecurityEvent;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@monitor.local'],
            [
                'name' => 'Administrador DevOps',
                'password' => Hash::make('admin123'),
                'is_admin' => true,
                'api_token' => null,
            ],
        );

        $services = collect([
            [
                'name' => 'Web Server - Google',
                'type' => 'WEB',
                'host' => 'https://www.google.com',
                'port' => 443,
                'description' => 'Serviço web público usado para validar disponibilidade HTTP.',
                'current_status' => 'UP',
            ],
            [
                'name' => 'DNS - Cloudflare',
                'type' => 'DNS',
                'host' => 'cloudflare.com',
                'port' => 53,
                'description' => 'Resolução DNS simulando dependência crítica de nomes.',
                'current_status' => 'UP',
            ],
            [
                'name' => 'SMTP - Gmail SMTP',
                'type' => 'SMTP',
                'host' => 'smtp.gmail.com',
                'port' => 587,
                'description' => 'Serviço SMTP usado para testar conectividade de e-mail.',
                'current_status' => 'UP',
            ],
            [
                'name' => 'Database - Local MySQL',
                'type' => 'DATABASE',
                'host' => '127.0.0.1',
                'port' => 3306,
                'description' => 'Banco MySQL local usado pela plataforma.',
                'current_status' => 'DOWN',
            ],
        ])->map(fn (array $data) => Service::create($data));

        foreach ($services as $service) {
            for ($i = 11; $i >= 0; $i--) {
                $latency = match ($service->type) {
                    'WEB' => random_int(80, 180),
                    'DNS' => random_int(120, 460),
                    'SMTP' => random_int(180, 520),
                    'DATABASE' => random_int(350, 700),
                    default => random_int(80, 300),
                };

                $status = $service->name === 'Database - Local MySQL' && $i < 4 ? 'DOWN' : 'UP';

                Metric::create([
                    'service_id' => $service->id,
                    'status' => $status,
                    'latency_ms' => $status === 'UP' ? $latency : null,
                    'http_status_code' => $service->type === 'WEB' ? ($status === 'UP' ? 200 : 503) : null,
                    'requests_per_second' => $service->type === 'WEB' && $i === 2 ? 1250 : random_int(100, 900),
                    'error_rate' => $status === 'UP' ? random_int(0, 15) / 10 : random_int(30, 90) / 10,
                    'error_count' => $status === 'UP' ? random_int(0, 8) : random_int(12, 80),
                    'active_connections' => random_int(20, 400),
                    'qps' => in_array($service->type, ['DATABASE', 'DNS'], true) ? random_int(50, 260) : 0,
                    'cpu_usage' => $service->type === 'DATABASE' ? random_int(2500, 8500) / 100 : null,
                    'memory_usage' => $service->type === 'DATABASE' ? random_int(3500, 9000) / 100 : null,
                    'io_wait' => $service->type === 'DATABASE' ? random_int(80, 1400) / 100 : null,
                    'db_size_mb' => $service->type === 'DATABASE' ? random_int(1024, 8192) : null,
                    'slow_queries' => $service->type === 'DATABASE' ? random_int(0, 12) : 0,
                    'dns_response_time' => $service->type === 'DNS' ? $latency : null,
                    'failed_resolutions' => $service->type === 'DNS' && $status === 'DOWN' ? random_int(1, 10) : 0,
                    'smtp_queue_size' => $service->type === 'SMTP' ? random_int(0, 40) : 0,
                    'smtp_delivery_rate' => $service->type === 'SMTP' ? random_int(9200, 9990) / 100 : null,
                    'email_volume' => $service->type === 'SMTP' ? random_int(20, 600) : 0,
                    'created_at' => now()->subMinutes($i * 5),
                    'updated_at' => now()->subMinutes($i * 5),
                ]);
            }
        }

        Alert::create([
            'service_id' => $services[1]->id,
            'level' => 'YELLOW',
            'title' => 'YELLOW: DNS - Cloudflare',
            'message' => 'O DNS apresentou latência elevada acima de 200 ms.',
            'sent_by_email' => true,
            'created_at' => now()->subMinutes(25),
        ]);

        Alert::create([
            'service_id' => $services[3]->id,
            'level' => 'RED',
            'title' => 'RED: Database - Local MySQL',
            'message' => 'O banco local ficou indisponível durante a última coleta.',
            'sent_by_email' => true,
            'created_at' => now()->subMinutes(10),
        ]);

        SecurityEvent::insert([
            [
                'service_id' => $services[0]->id,
                'type' => 'TRAFFIC_ANOMALY',
                'level' => 'HIGH',
                'description' => 'Web Server - Google ultrapassou 1000 requisições por segundo.',
                'source_ip' => '192.168.0.25',
                'created_at' => now()->subMinutes(35),
                'updated_at' => now()->subMinutes(35),
            ],
            [
                'service_id' => null,
                'type' => 'BRUTE_FORCE',
                'level' => 'HIGH',
                'description' => 'Cinco falhas de login detectadas para o mesmo IP.',
                'source_ip' => '10.0.0.44',
                'created_at' => now()->subMinutes(20),
                'updated_at' => now()->subMinutes(20),
            ],
            [
                'service_id' => null,
                'type' => 'CONFIG_CHANGE',
                'level' => 'MEDIUM',
                'description' => 'Alteração simulada no arquivo de configuração monitor_config.txt.',
                'source_ip' => '127.0.0.1',
                'created_at' => now()->subMinutes(15),
                'updated_at' => now()->subMinutes(15),
            ],
            [
                'service_id' => $services[2]->id,
                'type' => 'VULNERABILITY',
                'level' => 'CRITICAL',
                'description' => 'SMTP - Gmail SMTP possui vulnerabilidade simulada: TLS obrigatório não validado no runbook.',
                'source_ip' => null,
                'created_at' => now()->subMinutes(5),
                'updated_at' => now()->subMinutes(5),
            ],
            [
                'service_id' => $services[3]->id,
                'type' => 'VULNERABILITY',
                'level' => 'MEDIUM',
                'description' => 'Database - Local MySQL possui vulnerabilidade simulada: usuário de aplicação com privilégios amplos.',
                'source_ip' => null,
                'created_at' => now()->subMinutes(4),
                'updated_at' => now()->subMinutes(4),
            ],
        ]);

        LoginFailure::insert([
            [
                'source_ip' => '10.0.0.44',
                'email' => 'admin@monitor.local',
                'user_agent' => 'Seeder Browser',
                'created_at' => now()->subMinutes(24),
                'updated_at' => now()->subMinutes(24),
            ],
            [
                'source_ip' => '10.0.0.44',
                'email' => 'admin@monitor.local',
                'user_agent' => 'Seeder Browser',
                'created_at' => now()->subMinutes(23),
                'updated_at' => now()->subMinutes(23),
            ],
            [
                'source_ip' => '10.0.0.44',
                'email' => 'admin@monitor.local',
                'user_agent' => 'Seeder Browser',
                'created_at' => now()->subMinutes(22),
                'updated_at' => now()->subMinutes(22),
            ],
        ]);

        $configPath = storage_path('app/monitor_config.txt');
        File::ensureDirectoryExists(dirname($configPath));
        File::put($configPath, "monitoring_interval=60\nalert_email=devops@example.com\n");
        Cache::forever('monitor_config_hash', hash('sha256', File::get($configPath)));
    }
}

