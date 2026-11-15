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
        Schema::create('recipe_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_order_item_id')->constrained('pos_order_items')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('ingredient_product_id')->constrained('products')->onUpdate('cascade')->onDelete('restrict');
            $table->foreignId('warehouse_id')->constrained('inventory_warehouses')->onUpdate('cascade')->onDelete('restrict');
            $table->decimal('quantity_deducted', 14, 4);
            $table->string('unit');
            $table->timestamp('deduction_timestamp');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipe_deductions');
    }
};
