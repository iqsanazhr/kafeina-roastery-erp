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
        Schema::table('pos_orders', function (Blueprint $table) {
            $table->string('customer_name')->default('Tamu Kafeina')->after('order_type');
            $table->string('table_number')->nullable()->after('customer_name');
            $table->string('customer_phone')->nullable()->after('table_number');
            $table->text('notes')->nullable()->after('customer_phone');
            $table->string('payment_reference')->nullable()->after('payment_status');

            $table->index(['branch_id', 'created_at'], 'idx_orders_branch_created');
            $table->index('customer_name', 'idx_orders_customer_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_orders', function (Blueprint $table) {
            $table->dropIndex('idx_orders_branch_created');
            $table->dropIndex('idx_orders_customer_name');

            $table->dropColumn([
                'customer_name',
                'table_number',
                'customer_phone',
                'notes',
                'payment_reference',
            ]);
        });
    }
};
