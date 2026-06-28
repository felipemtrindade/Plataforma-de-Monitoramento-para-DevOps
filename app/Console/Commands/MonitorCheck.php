<?php

namespace App\Console\Commands;

use App\Models\Service;
use App\Services\NetworkMonitor;
use Illuminate\Console\Command;

class MonitorCheck extends Command
{
    protected $signature = 'monitor:check';

    protected $description = 'Coleta métricas dos serviços monitorados e gera alertas.';

    public function handle(NetworkMonitor $monitor): int
    {
        $services = Service::query()->orderBy('name')->get();

        if ($services->isEmpty()) {
            $this->warn('Nenhum serviço cadastrado.');

            return self::SUCCESS;
        }

        $this->info("Verificando {$services->count()} serviço(s)...");

        foreach ($services as $service) {
            $metric = $monitor->check($service);
            $latency = $metric->latency_ms === null ? 'sem resposta' : "{$metric->latency_ms} ms";
            $this->line("- {$service->name}: {$metric->status} ({$latency})");
        }

        $monitor->checkConfigChange();
        $this->info('Coleta finalizada.');

        return self::SUCCESS;
    }
}
