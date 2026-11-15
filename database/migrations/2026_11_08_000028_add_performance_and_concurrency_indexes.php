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
        // 1. stock_cards - Transaksi mutasi & rekonsiliasi stok frekuensi tinggi
        Schema::table('stock_cards', function (Blueprint $table) {
            $table->index(['reference_type', 'reference_id'], 'idx_stock_cards_reference');
            $table->index(['warehouse_id', 'product_id', 'created_at'], 'idx_stock_cards_wh_prod_created');
        });

        // 2. recipe_deductions - Auto-deduct resep instan & analisis kebocoran
        Schema::table('recipe_deductions', function (Blueprint $table) {
            $table->index(['pos_order_item_id', 'ingredient_product_id'], 'idx_recipe_deductions_item');
            $table->index(['warehouse_id', 'ingredient_product_id', 'deduction_timestamp'], 'idx_recipe_deductions_consumption');
        });

        // 3. pos_order_items - Detail item order POS & agregasi penjualan produk
        Schema::table('pos_order_items', function (Blueprint $table) {
            $table->index(['pos_order_id', 'product_id'], 'idx_pos_order_items_order_prod');
            $table->index(['product_id', 'created_at'], 'idx_pos_order_items_prod_created');
        });

        // 4. pos_orders - Rekonsiliasi kas per shift & analitik penjualan cabang
        Schema::table('pos_orders', function (Blueprint $table) {
            $table->index(['shift_id', 'payment_status', 'payment_method'], 'idx_pos_orders_shift_payment');
            $table->index(['branch_id', 'payment_status', 'created_at'], 'idx_pos_orders_branch_sales');
        });

        // 5. pos_shifts - Validasi cepat sesi kasir aktif
        Schema::table('pos_shifts', function (Blueprint $table) {
            $table->index(['branch_id', 'cashier_user_id', 'status'], 'idx_pos_shifts_active_lookup');
        });

        // 6. audit_logs - Audit trail forensik keamanan & aktivitas pengguna
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index(['event_type', 'created_at'], 'idx_audit_logs_event_time');
            $table->index(['user_id', 'created_at'], 'idx_audit_logs_user_timeline');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('idx_audit_logs_user_timeline');
            $table->dropIndex('idx_audit_logs_event_time');
        });

        Schema::table('pos_shifts', function (Blueprint $table) {
            $table->dropIndex('idx_pos_shifts_active_lookup');
        });

        Schema::table('pos_orders', function (Blueprint $table) {
            $table->dropIndex('idx_pos_orders_branch_sales');
            $table->dropIndex('idx_pos_orders_shift_payment');
        });

        Schema::table('pos_order_items', function (Blueprint $table) {
            $table->dropIndex('idx_pos_order_items_prod_created');
            $table->dropIndex('idx_pos_order_items_order_prod');
        });

        Schema::table('recipe_deductions', function (Blueprint $table) {
            $table->dropIndex('idx_recipe_deductions_consumption');
            $table->dropIndex('idx_recipe_deductions_item');
        });

        Schema::table('stock_cards', function (Blueprint $table) {
            $table->dropIndex('idx_stock_cards_wh_prod_created');
            $table->dropIndex('idx_stock_cards_reference');
        });
    }
};
