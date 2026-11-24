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
        // 1. Tambah kolom image, description, temperatures ke tabel products jika belum ada
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'image')) {
                $table->string('image')->nullable()->after('name');
            }
            if (! Schema::hasColumn('products', 'description')) {
                $table->text('description')->nullable()->after('name');
            }
            if (! Schema::hasColumn('products', 'temperatures')) {
                $table->json('temperatures')->nullable()->after('selling_price');
            }
        });

        // 2. Buat tabel product_variants untuk variasi ukuran cup, kemasan berat, dll.
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onUpdate('cascade')->onDelete('cascade');
            $table->string('name');
            $table->string('sku')->nullable()->unique();
            $table->decimal('price_adjustment', 15, 2)->default(0.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 3. Buat tabel product_modifiers untuk add-ons (susu alternatif, sirup, extra shot, topping)
        Schema::create('product_modifiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained('products')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->onUpdate('cascade')->onDelete('set null');
            $table->string('name');
            $table->string('group_name')->default('Ekstra & Modifiers');
            $table->decimal('price', 15, 2)->default(0.00);
            $table->foreignId('ingredient_product_id')->nullable()->constrained('products')->onUpdate('cascade')->onDelete('set null');
            $table->decimal('quantity_deducted', 12, 4)->nullable();
            $table->string('unit')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_modifiers');
        Schema::dropIfExists('product_variants');

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'temperatures')) {
                $table->dropColumn('temperatures');
            }
            if (Schema::hasColumn('products', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('products', 'image')) {
                $table->dropColumn('image');
            }
        });
    }
};
