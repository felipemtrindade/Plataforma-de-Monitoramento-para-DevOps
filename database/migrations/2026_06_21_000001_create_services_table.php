<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['WEB', 'DATABASE', 'DNS', 'SMTP']);
            $table->string('host');
            $table->unsignedInteger('port')->nullable();
            $table->text('description')->nullable();
            $table->enum('current_status', ['UP', 'DOWN'])->default('DOWN');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
