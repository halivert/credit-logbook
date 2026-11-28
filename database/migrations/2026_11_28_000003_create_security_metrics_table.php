<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Anonymous counts of suspicious events per hour: no user, IP or
        // device, only for monitoring
        Schema::create('security_metrics', function (Blueprint $table) {
            $table->id();
            $table->timestamp('hour');
            $table->string('type');
            $table->unsignedInteger('total');

            $table->unique(['hour', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_metrics');
    }
};
