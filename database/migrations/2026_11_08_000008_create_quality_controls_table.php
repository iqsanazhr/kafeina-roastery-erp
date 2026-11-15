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
        Schema::create('quality_controls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lot_id')->unique()->constrained('green_bean_lots')->onUpdate('cascade')->onDelete('restrict');
            $table->foreignId('inspector_id')->constrained('users')->onUpdate('cascade')->onDelete('restrict');
            $table->date('inspection_date');
            $table->decimal('moisture_content', 5, 2);
            $table->decimal('bulk_density_g_l', 7, 2);
            $table->unsignedInteger('primary_defects')->default(0);
            $table->unsignedInteger('secondary_defects')->default(0);
            $table->decimal('sca_cupping_score', 5, 2);
            $table->enum('qc_status', ['ACCEPTED', 'DOWNGRADED', 'REJECTED']);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quality_controls');
    }
};
