<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_failures', function (Blueprint $table) {
            $table->id();
            $table->string('source_ip');
            $table->string('email')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['source_ip', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_failures');
    }
};
