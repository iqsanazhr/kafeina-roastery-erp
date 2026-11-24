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
                'name' => 'store_manager',
                'display_name' => 'Store Manager',
                'description' => 'Manajemen operasional outlet, katalog produk & menu, penetapan harga jual varian, dan voucher.',
                'permissions' => [
                    'pos.*',
                    'pos_shifts.*',
                    'pos_orders.*',
                    'vouchers.*',
                    'material_requests.*',
                    'inventory.view',
                    'dashboard.view',
                    'products.*',
                    'pricing.*',
                    'recipes.*',
                    'categories.*',
                ],
            ],
            [
                'name' => 'supervisor',
                'display_name' => 'Outlet Supervisor',
                'description' => 'Supervisi operasional harian outlet, verifikasi closing shift, stok bar, dan katalog menu.',
                'permissions' => [
                    'pos.*',
                    'pos_shifts.*',
                    'pos_orders.*',
                    'vouchers.*',
                    'material_requests.*',
                    'inventory.view',
                    'dashboard.view',
                    'products.*',
                    'pricing.*',
                    'recipes.*',
                    'categories.*',
                ],
            ],
            [
                'name' => 'procurement_officer',
                'display_name' => 'Procurement Officer',
                'description' => 'Manajemen data petani mitra, penerbitan PO green beans, dan inspeksi lab mutu QC.',
                'permissions' => [
                    'suppliers.*',
                    'purchase_orders.*',
                    'green_bean_lots.*',
                    'quality_controls.view',
                    'inventory.view',
                    'products.view',
                ],
            ],
            [
                'name' => 'procurement',
                'display_name' => 'Procurement Officer (Alias)',
                'description' => 'Alias wewenang pengadaan biji mentah dan kemasan.',
                'permissions' => [
                    'suppliers.*',
                    'purchase_orders.*',
                    'green_bean_lots.*',
                    'quality_controls.*',
                    'inventory.view',
                    'products.view',
                ],
            ],
            [
                'name' => 'qc_inspector',
                'display_name' => 'QC Lab Inspector',
                'description' => 'Pemeriksaan mutu fisik, kadar air green beans, dan penilaian citarasa cupping SCAA.',
                'permissions' => [
                    'quality_controls.*',
                    'green_bean_lots.view',
                    'suppliers.view',
                ],
            ],
            [
                'name' => 'head_roaster',
                'display_name' => 'Head Roaster',
                'description' => 'Manajemen kurva sangrai, work order pemanggangan, formula resep BOM bar, dan HPP batch.',
                'permissions' => [
                    'roasting_profiles.*',
                    'work_orders.*',
                    'boms.*',
                    'products.view',
                    'green_bean_lots.view',
                    'yield_loss.*',
                    'recipes.*',
                ],
            ],
            [
                'name' => 'roaster',
                'display_name' => 'Roaster Staff (Alias)',
                'description' => 'Alias operasional sangrai roastery central.',
                'permissions' => [
                    'roasting_profiles.*',
                    'work_orders.*',
                    'boms.*',
                    'products.view',
                    'green_bean_lots.view',
                    'yield_loss.*',
                    'recipes.*',
                ],
            ],
            [
                'name' => 'warehouse_staff',
                'display_name' => 'Warehouse & Logistics Staff',
                'description' => 'Penerimaan stok pusat, pemenuhan Material Request outlet, penerbitan Surat Jalan ber-QR, dan alokasi FIFO.',
                'permissions' => [
                    'inventory.*',
                    'warehouse_stocks.*',
                    'stock_cards.*',
                    'material_requests.*',
                    'delivery_notes.*',
                    'products.view',
                ],
            ],
            [
                'name' => 'warehouse',
                'display_name' => 'Warehouse (Alias)',
                'description' => 'Alias petugas logistik dan inventori.',
                'permissions' => [
                    'inventory.*',
                    'warehouse_stocks.*',
                    'stock_cards.*',
                    'material_requests.*',
                    'delivery_notes.*',
                    'products.view',
                ],
            ],
            [
                'name' => 'branch_cashier',
                'display_name' => 'Branch Cashier / Barista',
                'description' => 'Operasional tablet Web POS, validasi modal shift kasir, transaksi multi-metode, dan auto-deduct resep.',
                'permissions' => [
                    'pos.*',
                    'pos_shifts.*',
                    'pos_orders.*',
                    'vouchers.view',
                    'vouchers.redeem',
                    'products.view',
                    'recipe_deductions.view',
                ],
            ],
            [
                'name' => 'cashier',
                'display_name' => 'Cashier (Alias)',
                'description' => 'Alias staf kasir dan barista.',
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
