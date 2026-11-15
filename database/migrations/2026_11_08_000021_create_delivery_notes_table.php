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
        Schema::create('delivery_notes', function (Blueprint $table) {
            $table->id();
            $table->string('delivery_number')->unique();
            $table->foreignId('request_id')->nullable()->constrained('material_requests')->onUpdate('cascade')->onDelete('set null');
            $table->foreignId('source_warehouse_id')->constrained('inventory_warehouses')->onUpdate('cascade')->onDelete('restrict');
            $table->foreignId('dest_warehouse_id')->constrained('inventory_warehouses')->onUpdate('cascade')->onDelete('restrict');
            $table->foreignId('dispatched_by_user_id')->constrained('users')->onUpdate('cascade')->onDelete('restrict');
            $table->string('courier_name')->nullable();
            $table->string('tracking_code')->nullable();
            $table->enum('status', ['DISPATCHED', 'DELIVERED', 'RECONCILED'])->default('DISPATCHED');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_notes');
    }
};
