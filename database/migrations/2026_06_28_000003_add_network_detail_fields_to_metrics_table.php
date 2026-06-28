<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('metrics', function (Blueprint $table) {
            $table->unsignedSmallInteger('http_status_code')->nullable()->after('latency_ms');
            $table->unsignedInteger('error_count')->default(0)->after('error_rate');
            $table->decimal('cpu_usage', 5, 2)->nullable()->after('qps');
            $table->decimal('memory_usage', 5, 2)->nullable()->after('cpu_usage');
            $table->decimal('io_wait', 5, 2)->nullable()->after('memory_usage');
            $table->unsignedInteger('db_size_mb')->nullable()->after('io_wait');
            $table->unsignedInteger('slow_queries')->default(0)->after('db_size_mb');
            $table->unsignedInteger('failed_resolutions')->default(0)->after('dns_response_time');
            $table->decimal('smtp_delivery_rate', 5, 2)->nullable()->after('smtp_queue_size');
            $table->unsignedInteger('email_volume')->default(0)->after('smtp_delivery_rate');
        });
    }

    public function down(): void
    {
        Schema::table('metrics', function (Blueprint $table) {
            $table->dropColumn([
                'http_status_code',
                'error_count',
                'cpu_usage',
                'memory_usage',
                'io_wait',
                'db_size_mb',
                'slow_queries',
                'failed_resolutions',
                'smtp_delivery_rate',
                'email_volume',
            ]);
        });
    }
};
