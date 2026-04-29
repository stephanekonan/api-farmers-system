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
            $table->id();
            $table->string('reference')->unique();

            $table->foreignId('farmer_id')
                ->constrained('farmers')
                ->restrictOnDelete();

            $table->foreignId('operator_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->decimal('subtotal_fcfa', 12, 2);

            $table->enum('payment_method', ['cash', 'credit']);

            $table->decimal('interest_rate', 5, 4)->nullable();
            $table->decimal('interest_amount_fcfa', 12, 2)->nullable();

            $table->decimal('total_fcfa', 12, 2);

            $table->enum('status', ['completed', 'cancelled'])->default('completed');
            $table->text('notes')->nullable();
            $table->timestamp('transacted_at')->useCurrent();
            $table->timestamps();
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
