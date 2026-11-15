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
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->string('wo_number')->unique();
            $table->foreignId('lot_id')->constrained('green_bean_lots')->onUpdate('cascade')->onDelete('restrict');
            $table->foreignId('profile_id')->constrained('roasting_profiles')->onUpdate('cascade')->onDelete('restrict');
            $table->foreignId('roaster_id')->constrained('users')->onUpdate('cascade')->onDelete('restrict');
            $table->decimal('green_weight_grams', 12, 2);
            $table->decimal('roasted_weight_grams', 12, 2)->nullable();
            $table->decimal('yield_loss_percentage', 5, 2)->nullable();
            $table->enum('status', ['SCHEDULED', 'IN_PROGRESS', 'COMPLETED', 'FAILED'])->default('SCHEDULED');
            $table->dateTime('roast_start_time')->nullable();
            $table->dateTime('roast_end_time')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_orders');
    }
};
