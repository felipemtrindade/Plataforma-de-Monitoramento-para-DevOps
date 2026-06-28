<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Metric extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'status',
        'latency_ms',
        'http_status_code',
        'requests_per_second',
        'error_rate',
        'error_count',
        'active_connections',
        'qps',
        'cpu_usage',
        'memory_usage',
        'io_wait',
        'db_size_mb',
        'slow_queries',
        'dns_response_time',
        'failed_resolutions',
        'smtp_queue_size',
        'smtp_delivery_rate',
        'email_volume',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
