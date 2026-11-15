<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'ROASTED_BEANS',
                'description' => 'Biji kopi matang hasil sangrai roastery (retail pack & bulk espresso blend).',
            ],
            [
                'name' => 'BEVERAGE',
                'description' => 'Menu minuman siap saji di bar outlet POS (Espresso based, Manual brew, Tea).',
            ],
            [
                'name' => 'INGREDIENT',
                'description' => 'Bahan baku mentah dan komponen resep (Green beans, susu pasteurisasi, sirup, kemasan cup).',
            ],
            [
                'name' => 'MERCHANDISE',
                'description' => 'Peralatan seduh kopi manual, apparel roastery, dan tumbler merchandise.',
            ],
        ];

        foreach ($categories as $cat) {
            ProductCategory::updateOrCreate(
                ['name' => $cat['name']],
                $cat
            );
        }
    }
}
