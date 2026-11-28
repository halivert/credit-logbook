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
            $table->date('date');
            // Negative for returns
            $table->decimal('amount', 19, 4);

            $table->integer('installment_count')->nullable();
            $table->decimal('installment_amount', 19, 4)->nullable();
            $table->decimal('commission', 19, 4)->nullable();
            $table->decimal('interest_rate', 6, 2)->nullable();
            // When the bank applied it, if different from the purchase date
            $table->date('applied_on')->nullable();

            $table->timestamps(3);

            $table->index(['credit_card_id', 'date']);
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
