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
        Schema::create('material_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->unique();
            $table->foreignId('from_branch_id')->constrained('branches')->onUpdate('cascade')->onDelete('restrict');
            $table->foreignId('to_branch_id')->constrained('branches')->onUpdate('cascade')->onDelete('restrict');
            $table->foreignId('requested_by_user_id')->constrained('users')->onUpdate('cascade')->onDelete('restrict');
            $table->enum('status', ['PENDING', 'APPROVED', 'IN_TRANSIT', 'RECEIVED', 'REJECTED'])->default('PENDING');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_requests');
    }
};
