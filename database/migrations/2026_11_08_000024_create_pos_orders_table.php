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
        Schema::create('pos_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('shift_id')->constrained('pos_shifts')->onUpdate('cascade')->onDelete('restrict');
            $table->foreignId('cashier_id')->constrained('users')->onUpdate('cascade')->onDelete('restrict');
            $table->foreignId('branch_id')->constrained('branches')->onUpdate('cascade')->onDelete('restrict');
            $table->enum('order_type', ['DINE_IN', 'TAKE_AWAY', 'NON_REVENUE'])->default('DINE_IN');
            $table->enum('non_revenue_reason', ['CALIBRATION', 'TESTER', 'STAFF_QUOTA'])->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->enum('payment_method', ['CASH', 'QRIS_MIDTRANS', 'DEBIT_CARD', 'NON_REVENUE', 'SPLIT_BILL']);
            $table->enum('payment_status', ['PENDING', 'PAID', 'VOID', 'REFUNDED'])->default('PENDING');
            $table->timestamps();

            $table->index(['branch_id', 'shift_id', 'payment_status'], 'idx_orders_branch_shift');
            $table->index('created_at', 'idx_orders_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_orders');
    }
};
