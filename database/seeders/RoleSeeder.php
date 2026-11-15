<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'super_admin',
                'display_name' => 'Super Administrator',
                'description' => 'Akses penuh tanpa batasan ke seluruh modul sistem KAFEINA ERP.',
                'permissions' => ['*'],
            ],
            [
                'name' => 'procurement',
                'display_name' => 'Procurement Officer',
                'description' => 'Manajemen data petani mitra, penerbitan PO green beans, dan inspeksi lab mutu QC.',
                'permissions' => [
                    'suppliers.*',
                    'purchase_orders.*',
                    'green_bean_lots.*',
                    'quality_controls.*',
                ],
            ],
            [
                'name' => 'roaster',
                'display_name' => 'Head Roaster',
                'description' => 'Manajemen kurva sangrai, work order pemanggangan, monitoring yield loss, dan HPP riil batch.',
                'permissions' => [
                    'roasting_profiles.*',
                    'work_orders.*',
                    'boms.*',
                    'products.view',
                    'green_bean_lots.view',
                ],
            ],
            [
                'name' => 'warehouse',
                'display_name' => 'Warehouse & Logistics Staff',
                'description' => 'Penerimaan stok pusat, pemenuhan Material Request outlet, penerbitan Surat Jalan ber-QR, dan alokasi FIFO.',
                'permissions' => [
                    'inventory.*',
                    'warehouse_stocks.*',
                    'stock_cards.*',
                    'material_requests.*',
                    'delivery_notes.*',
                ],
            ],
            [
                'name' => 'cashier',
                'display_name' => 'Cashier / Barista',
                'description' => 'Operasional tablet Web POS, validasi modal shift kasir, transaksi multi-metode, dan auto-deduct resep.',
                'permissions' => [
                    'pos.*',
                    'pos_shifts.*',
                    'pos_orders.*',
                    'recipe_deductions.view',
                    'products.view',
                ],
            ],
        ];

        foreach ($roles as $roleData) {
            Role::updateOrCreate(
                ['name' => $roleData['name']],
                [
                    'display_name' => $roleData['display_name'],
                    'description' => $roleData['description'],
                    'permissions' => $roleData['permissions'],
                ]
            );
        }
    }
}
