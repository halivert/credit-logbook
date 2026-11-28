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
        Schema::create('purchases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('credit_card_id')->constrained()->cascadeOnDelete();
            $table->string('concept');
            $table->timestamp('datetime', 3);
            // Negative for returns
            $table->decimal('amount', 19, 4);

            $table->integer('installment_count')->nullable();
            $table->decimal('installment_amount', 19, 4)->nullable();
            $table->decimal('commission', 19, 4)->nullable();
            $table->decimal('interest_rate', 6, 2)->nullable();
            // When the bank applied it, if different from the purchase date
            $table->timestamp('applied_at', 3)->nullable();

            $table->timestamps(3);

            $table->index(['credit_card_id', 'datetime']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
