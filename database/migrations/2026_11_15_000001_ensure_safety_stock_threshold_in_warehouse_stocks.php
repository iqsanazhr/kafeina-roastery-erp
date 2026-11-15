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
        if (Schema::hasTable('warehouse_stocks') && ! Schema::hasColumn('warehouse_stocks', 'safety_stock_threshold')) {
            Schema::table('warehouse_stocks', function (Blueprint $table) {
                $table->decimal('safety_stock_threshold', 14, 4)->default(100.00)->after('reserved_quantity');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('warehouse_stocks') && Schema::hasColumn('warehouse_stocks', 'safety_stock_threshold')) {
            Schema::table('warehouse_stocks', function (Blueprint $table) {
                $table->dropColumn('safety_stock_threshold');
            });
        }
    }
};
