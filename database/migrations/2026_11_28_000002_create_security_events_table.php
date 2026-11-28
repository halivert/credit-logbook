<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sign ins and changes to how an account signs in, kept 90 days.
        // Failed attempts on emails without an account have no user (and
        // never the email): they only count for monitoring.
        Schema::create('security_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('type');
            // How a sign in happened: password, two_factor, passkey...
            $table->string('method')->nullable();
            // Name of the passkey added or removed
            $table->string('detail')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('created_at', 3);

            $table->index(['user_id', 'created_at']);
            $table->index(['type', 'created_at']);
            $table->index(['ip_address', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_events');
    }
};
