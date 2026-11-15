<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $branches = [
            [
                'code' => 'HQ-CENTRAL',
                'name' => 'Kafeina Central Roastery & Lab Hub',
                'type' => 'CENTRAL_ROASTERY',
                'address' => 'Kawasan Industri Cilandak Commercial Estate Kav. 12, Jakarta Selatan',
                'phone' => '021-7890123',
                'is_active' => true,
            ],
            [
                'code' => 'OUT-SENO',
                'name' => 'Kafeina Flagship Senopati',
                'type' => 'RETAIL_BRANCH',
                'address' => 'Jl. Senopati No. 44, Kebayoran Baru, Jakarta Selatan',
                'phone' => '021-5234567',
                'is_active' => true,
            ],
            [
                'code' => 'OUT-BRG',
                'name' => 'Kafeina Specialty Braga Bandung',
                'type' => 'RETAIL_BRANCH',
                'address' => 'Jl. Braga No. 88, Sumur Bandung, Kota Bandung',
                'phone' => '022-4239876',
                'is_active' => true,
            ],
            [
                'code' => 'OUT-MLO',
                'name' => 'Kafeina Heritage Malioboro Jogja',
                'type' => 'RETAIL_BRANCH',
                'address' => 'Jl. Malioboro No. 120, Sosromenduran, Gedong Tengen, Yogyakarta',
                'phone' => '0274-567890',
                'is_active' => true,
            ],
        ];

        foreach ($branches as $branch) {
            Branch::updateOrCreate(
                ['code' => $branch['code']],
                $branch
            );
        }
    }
}
