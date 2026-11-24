<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductModifier;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;

class ProductModifierSeeder extends Seeder
{
    public function run(): void
    {
        $oatMilk = Product::where('sku', 'RAW-MILK-OAT')->first();
        $vanillaSyrup = Product::where('sku', 'RAW-SYRUP-VANILLA')->first();
        $caramelSyrup = Product::where('sku', 'RAW-SYRUP-CARAMEL')->first();
        $houseBeans = Product::where('sku', 'RAW-RB-HOUSE')->first();

        $bevCategory = ProductCategory::where('name', 'LIKE', '%Espresso%')->first();

        // 1. Master Modifiers & Ekstra Barista
        $modifiers = [
            [
                'name' => 'Oat Milk (Oatly Barista)',
                'group_name' => 'Susu Alternatif',
                'price' => 8000.00,
                'ingredient_product_id' => $oatMilk?->id,
                'quantity_deducted' => 180.00,
                'unit' => 'ml',
            ],
            [
                'name' => 'Almond Milk (Unsweetened)',
                'group_name' => 'Susu Alternatif',
                'price' => 10000.00,
                'ingredient_product_id' => null,
                'quantity_deducted' => 180.00,
                'unit' => 'ml',
            ],
            [
                'name' => 'Extra Espresso Shot (18g)',
                'group_name' => 'Custom Shot',
                'price' => 6000.00,
                'ingredient_product_id' => $houseBeans?->id,
                'quantity_deducted' => 18.00,
                'unit' => 'gram',
            ],
            [
                'name' => 'Artisan Vanilla Syrup (20ml)',
                'group_name' => 'Sirup & Flavour',
                'price' => 5000.00,
                'ingredient_product_id' => $vanillaSyrup?->id,
                'quantity_deducted' => 20.00,
                'unit' => 'ml',
            ],
            [
                'name' => 'Salted Caramel Sauce (20ml)',
                'group_name' => 'Sirup & Flavour',
                'price' => 5000.00,
                'ingredient_product_id' => $caramelSyrup?->id,
                'quantity_deducted' => 20.00,
                'unit' => 'ml',
            ],
            [
                'name' => 'Vanilla Ice Cream Scoop (Affogato)',
                'group_name' => 'Topping & Float',
                'price' => 8000.00,
                'ingredient_product_id' => null,
                'quantity_deducted' => 1.00,
                'unit' => 'pcs',
            ],
            [
                'name' => 'Organic Blossom Honey',
                'group_name' => 'Pemanis Alami',
                'price' => 4000.00,
                'ingredient_product_id' => null,
                'quantity_deducted' => 15.00,
                'unit' => 'ml',
            ],
            [
                'name' => 'Artisan Whipped Cream',
                'group_name' => 'Topping & Float',
                'price' => 5000.00,
                'ingredient_product_id' => null,
                'quantity_deducted' => 30.00,
                'unit' => 'gram',
            ],
        ];

        foreach ($modifiers as $mod) {
            ProductModifier::updateOrCreate(
                ['name' => $mod['name']],
                array_merge($mod, [
                    'category_id' => $bevCategory?->id,
                    'is_active' => true,
                ])
            );
        }

        // 2. Sample Varian Ukuran Cup untuk Menu Minuman
        $espressoProducts = Product::where('is_raw_material', false)
            ->where('sku', 'LIKE', 'PRD-ESP%')
            ->get();

        foreach ($espressoProducts as $prod) {
            ProductVariant::updateOrCreate(
                ['product_id' => $prod->id, 'name' => 'Regular 8oz'],
                [
                    'sku' => $prod->sku.'-REG8',
                    'price_adjustment' => 0.00,
                    'is_active' => true,
                ]
            );

            ProductVariant::updateOrCreate(
                ['product_id' => $prod->id, 'name' => 'Large 12oz (+Rp 5.000)'],
                [
                    'sku' => $prod->sku.'-LRG12',
                    'price_adjustment' => 5000.00,
                    'is_active' => true,
                ]
            );
        }

        // 3. Sample Varian Kemasan untuk Retail Beans
        $retailBeans = Product::where('sku', 'LIKE', 'PRD-RET%')->get();
        foreach ($retailBeans as $prod) {
            ProductVariant::updateOrCreate(
                ['product_id' => $prod->id, 'name' => 'Kemasan 250g Pouch'],
                [
                    'sku' => $prod->sku.'-250G',
                    'price_adjustment' => 0.00,
                    'is_active' => true,
                ]
            );

            ProductVariant::updateOrCreate(
                ['product_id' => $prod->id, 'name' => 'Kemasan 1kg Bulk (+Rp 220.000)'],
                [
                    'sku' => $prod->sku.'-1KG',
                    'price_adjustment' => 220000.00,
                    'is_active' => true,
                ]
            );
        }
    }
}
