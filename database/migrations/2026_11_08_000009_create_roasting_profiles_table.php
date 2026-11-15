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
        Schema::create('roasting_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('profile_code')->unique();
            $table->string('name');
            $table->enum('roast_level', ['LIGHT', 'MEDIUM', 'MEDIUM_DARK', 'DARK']);
            $table->decimal('charge_temp_celsius', 5, 2);
            $table->decimal('turning_point_celsius', 5, 2);
            $table->decimal('development_time_ratio', 5, 2);
            $table->string('target_air_flow')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roasting_profiles');
    }
};
