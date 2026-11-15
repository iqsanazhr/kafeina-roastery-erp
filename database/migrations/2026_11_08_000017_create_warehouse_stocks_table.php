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
        Schema::create('warehouse_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('inventory_warehouses')->onUpdate('cascade')->onDelete('restrict');
            $table->foreignId('product_id')->constrained('products')->onUpdate('cascade')->onDelete('restrict');
            $table->decimal('quantity', 14, 4)->default(0);
            $table->decimal('reserved_quantity', 14, 4)->default(0);
            $table->decimal('safety_stock_threshold', 14, 4)->default(100.00);
            $table->date('roasted_date')->nullable();
            $table->timestamp('last_stock_take_at')->nullable();
            $table->timestamps();

            $table->index(['warehouse_id', 'product_id', 'roasted_date', 'quantity'], 'idx_wh_stock_fifo');
            $table->index(['warehouse_id', 'product_id'], 'idx_wh_stock_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_stocks');
    }
};
