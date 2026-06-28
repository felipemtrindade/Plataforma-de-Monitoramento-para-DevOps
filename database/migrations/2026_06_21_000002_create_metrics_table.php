<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['UP', 'DOWN']);
            $table->unsignedInteger('latency_ms')->nullable();
            $table->unsignedInteger('requests_per_second')->default(0);
            $table->decimal('error_rate', 5, 2)->default(0);
            $table->unsignedInteger('active_connections')->default(0);
            $table->unsignedInteger('qps')->default(0);
            $table->unsignedInteger('dns_response_time')->nullable();
            $table->unsignedInteger('smtp_queue_size')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metrics');
    }
};
