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
        Schema::create('farmers', function (Blueprint $table) {
            $table->id();

            $table->string('card_identifier')->unique();

            $table->string('firstname');
            $table->string('lastname');
            $table->string('phone')->unique();

            $table->string('village')->nullable();
            $table->string('region')->nullable();

            $table->decimal('credit_limit_fcfa', 12, 2)->default(50000.00);

            $table->decimal('total_outstanding_debt', 12, 2)->default(0.00);

            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('farmers');
    }
};
