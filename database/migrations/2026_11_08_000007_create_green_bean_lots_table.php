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
        Schema::create('green_bean_lots', function (Blueprint $table) {
            $table->id();
            $table->string('lot_number')->unique();
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->onUpdate('cascade')->onDelete('restrict');
            $table->string('variety');
            $table->string('origin_region');
            $table->enum('process_type', ['WASHED', 'NATURAL', 'HONEY', 'ANAEROBIC', 'EXPERIMENTAL']);
            $table->year('harvest_year');
            $table->decimal('initial_weight_grams', 12, 2);
            $table->decimal('current_weight_grams', 12, 2);
            $table->decimal('price_per_gram', 12, 4);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('green_bean_lots');
    }
};
