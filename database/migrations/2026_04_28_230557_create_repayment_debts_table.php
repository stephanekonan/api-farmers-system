<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('repayment_debt', function (Blueprint $table) {
            $table->id();

            $table->foreignId('repayment_id')
                ->constrained('repayments')
                ->cascadeOnDelete();

            $table->foreignId('debt_id')
                ->constrained('debts')
                ->restrictOnDelete();

            $table->decimal('amount_applied_fcfa', 12, 2);

            $table->timestamps();

            $table->unique(['repayment_id', 'debt_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repayment_debts');
    }
};
