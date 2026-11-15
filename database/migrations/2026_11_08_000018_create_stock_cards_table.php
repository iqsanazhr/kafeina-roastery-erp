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
        Schema::create('stock_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('inventory_warehouses')->onUpdate('cascade')->onDelete('restrict');
            $table->foreignId('product_id')->constrained('products')->onUpdate('cascade')->onDelete('restrict');
            $table->foreignId('lot_id')->nullable()->constrained('green_bean_lots')->onUpdate('cascade')->onDelete('set null');
            $table->enum('reference_type', [
                'PO_RECEIPT',
                'ROAST_CHARGE',
                'ROAST_OUTPUT',
                'TRANSFER_DISPATCH',
                'TRANSFER_RECEIVE',
                'POS_SALE',
                'ADJUSTMENT',
            ]);
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->decimal('quantity_in', 14, 4)->default(0);
            $table->decimal('quantity_out', 14, 4)->default(0);
            $table->decimal('balance_after', 14, 4)->default(0);
            $table->decimal('cost_per_unit', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_cards');
    }
};
