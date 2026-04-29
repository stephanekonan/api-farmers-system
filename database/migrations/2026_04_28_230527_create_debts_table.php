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
        Schema::create('debts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('farmer_id')
                ->constrained('farmers')
                ->restrictOnDelete();

            $table->foreignId('transaction_id')
                ->constrained('transactions')
                ->restrictOnDelete();

            $table->decimal('original_amount_fcfa', 12, 2);

            $table->decimal('paid_amount_fcfa', 12, 2)->default(0.00);

            $table->decimal('remaining_amount_fcfa', 12, 2);

            $table->enum('status', ['open', 'partially_paid', 'paid'])->default('open');

            $table->timestamp('incurred_at');
            $table->timestamp('fully_paid_at')->nullable();
            $table->timestamps();

            $table->index(['farmer_id', 'status', 'incurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('debts');
    }
};
