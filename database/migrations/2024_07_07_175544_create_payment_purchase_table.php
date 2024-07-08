<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_purchase', function (Blueprint $table) {
            $table->foreignUuid('payment_id')->constrained();
            $table->foreignUuid('purchase_id')->constrained();
            $table->integer('installment_number')->nullable();

            $table->primary(['payment_id', 'purchase_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_purchase');
    }
};
