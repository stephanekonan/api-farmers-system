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
        Schema::create('repayments', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();

            $table->foreignId('farmer_id')
                ->constrained('farmers')
                ->restrictOnDelete();

            $table->foreignId('operator_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->decimal('commodity_kg', 10, 3);

            $table->decimal('commodity_rate_fcfa_per_kg', 10, 2);

            $table->decimal('total_fcfa_value', 12, 2);

            $table->text('notes')->nullable();
            $table->timestamp('repaid_at')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repayments');
    }
};
