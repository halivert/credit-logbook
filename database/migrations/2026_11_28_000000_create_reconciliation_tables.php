<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // What the bank's statement said for a closing
        Schema::create('bank_statements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('credit_card_id')->constrained()->cascadeOnDelete();
            $table->date('closing_date');
            // Pago para no generar intereses
            $table->decimal('to_avoid_interest', 19, 4);
            $table->timestamps(3);

            $table->unique(['credit_card_id', 'closing_date']);
        });

        // Corrections when the logbook and the bank don't match
        Schema::create('adjustments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('credit_card_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            // Positive is a charge, negative a credit
            $table->decimal('amount', 19, 4);
            $table->string('concept');
            $table->timestamps(3);

            $table->index(['credit_card_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adjustments');
        Schema::dropIfExists('bank_statements');
    }
};
