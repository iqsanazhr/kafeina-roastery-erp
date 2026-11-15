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
        Schema::create('pos_shifts', function (Blueprint $table) {
            $table->id();
            $table->string('shift_code')->unique();
            $table->foreignId('cashier_user_id')->constrained('users')->onUpdate('cascade')->onDelete('restrict');
            $table->foreignId('branch_id')->constrained('branches')->onUpdate('cascade')->onDelete('restrict');
            $table->decimal('opening_cash', 15, 2)->default(0);
            $table->decimal('closing_cash_actual', 15, 2)->nullable();
            $table->decimal('closing_cash_system', 15, 2)->nullable();
            $table->decimal('cash_difference', 15, 2)->nullable();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->enum('status', ['OPEN', 'CLOSED'])->default('OPEN');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_shifts');
    }
};
