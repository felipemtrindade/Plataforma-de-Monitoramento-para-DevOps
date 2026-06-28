<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\Metric;
use App\Models\Service;
use Illuminate\Support\Facades\Mail;

class AlertService
{
    public function evaluate(Service $service, Metric $metric): ?Alert
    {
        $level = $this->levelFor($metric);
        $cooldownMinutes = (int) env('ALERT_COOLDOWN_MINUTES', 15);

        if ($level === 'GREEN') {
            return null;
        }

        $title = "{$level}: {$service->name}";
        $message = $metric->status === 'DOWN'
            ? "O serviço {$service->name} está indisponível em {$service->host}:{$service->port}."
            : "O serviço {$service->name} está com latência alta ({$metric->latency_ms} ms).";

        $recentSimilarAlert = Alert::query()
            ->where('service_id', $service->id)
            ->where('level', $level)
            ->where('title', $title)
            ->where('created_at', '>=', now()->subMinutes($cooldownMinutes))
            ->exists();

        if ($recentSimilarAlert) {
            return null;
        }

        $sentByEmail = $this->sendEmail($title, $message);

        return Alert::create([
            'service_id' => $service->id,
            'level' => $level,
            'title' => $title,
            'message' => $message,
            'sent_by_email' => $sentByEmail,
        ]);
    }

    public function levelFor(Metric $metric): string
    {
        if ($metric->status === 'DOWN' || $metric->latency_ms === null || $metric->latency_ms > 500) {
            return 'RED';
        }

        if ($metric->latency_ms >= 200) {
            return 'YELLOW';
        }

        return 'GREEN';
    }

    private function sendEmail(string $title, string $message): bool
    {
        $to = config('mail.monitoring_to', env('MONITORING_ALERT_EMAIL', 'devops@example.com'));

        try {
            Mail::raw($message, function ($mail) use ($to, $title) {
                $mail->to($to)->subject("[Monitoramento DevOps] {$title}");
            });

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}

