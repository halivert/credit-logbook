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
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('credit_card_id')->constrained();
            $table->string('concept');
            $table->dateTime('datetime', 3);
            $table->decimal('amount', 19, 4);
            $table->integer('deadline_months')->nullable();
            $table->decimal('commission', 19, 4)->nullable();
            $table->decimal('interest_rate', 6, 2)->nullable();
            $table->foreignUuid('parent_transaction_id')->nullable();
            $table->timestamps(3);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
