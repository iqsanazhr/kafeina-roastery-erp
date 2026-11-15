<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Bom;
use App\Models\BomItem;
use App\Models\Branch;
use App\Models\DeliveryNote;
use App\Models\DeliveryNoteItem;
use App\Models\GreenBeanLot;
use App\Models\InventoryWarehouse;
use App\Models\MaterialRequest;
use App\Models\MaterialRequestItem;
use App\Models\PosOrder;
use App\Models\PosOrderItem;
use App\Models\PosShift;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductRecipe;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\QualityControl;
use App\Models\RecipeDeduction;
use App\Models\RoastingProfile;
use App\Models\Role;
use App\Models\StockCard;
use App\Models\Supplier;
use App\Models\User;
use App\Models\WarehouseStock;
use App\Models\WorkOrder;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class KafeinaRealDataSeeder extends Seeder
{
    /**
     * Run the comprehensive real interconnected specialty coffee data seeder.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // 1. MASTER ROLES & PERMISSIONS
        $roles = [
            'super_admin' => [
                'display_name' => 'Super Administrator',
                'description' => 'Akses penuh seluruh cabang & modul sistem KAFEINA ERP.',
                'permissions' => ['*'],
            ],
            'procurement_officer' => [
                'display_name' => 'Procurement Officer',
                'description' => 'Pengadaan biji mentah dari kelompok tani & manajemen supplier.',
                'permissions' => ['suppliers.*', 'purchase_orders.*', 'green_bean_lots.*', 'quality_controls.view', 'inventory.view'],
            ],
            'qc_inspector' => [
                'display_name' => 'QC Lab Cupper',
                'description' => 'Pemeriksaan fisik green bean, kadar air, bulk density & SCA Cupping.',
                'permissions' => ['quality_controls.*', 'green_bean_lots.view', 'suppliers.view'],
            ],
            'head_roaster' => [
                'display_name' => 'Head Roaster',
                'description' => 'Operasional mesin Giesen W15A, manajemen profil sangrai & yield loss.',
                'permissions' => ['roasting_profiles.*', 'work_orders.*', 'boms.*', 'products.view', 'green_bean_lots.view', 'yield_loss.*'],
            ],
            'warehouse_staff' => [
                'display_name' => 'Logistics & Warehouse',
                'description' => 'Penerimaan gudang, alokasi FIFO, dan surat jalan transfer cabang.',
                'permissions' => ['inventory.*', 'warehouse_stocks.*', 'stock_cards.*', 'material_requests.*', 'delivery_notes.*'],
            ],
            'store_manager' => [
                'display_name' => 'Store Manager',
                'description' => 'Supervisi operasional cabang, shift kasir, dan audit kebocoran bar.',
                'permissions' => ['pos.*', 'pos_shifts.*', 'pos_orders.*', 'vouchers.*', 'material_requests.*', 'inventory.view', 'dashboard.view'],
            ],
            'branch_cashier' => [
                'display_name' => 'Lead Barista & Cashier',
                'description' => 'Operasional terminal POS layar sentuh, split bill, dan auto-deduct resep.',
                'permissions' => ['pos.*', 'pos_shifts.*', 'pos_orders.*', 'vouchers.view', 'vouchers.redeem', 'products.view', 'recipe_deductions.view'],
            ],
            'supervisor' => [
                'display_name' => 'Floor Supervisor',
                'description' => 'Otorisasi 6-digit PIN rekonsiliasi kasir dan pembatalan pesanan.',
                'permissions' => ['pos.*', 'pos_shifts.*', 'pos_orders.*', 'vouchers.*', 'material_requests.*', 'inventory.view', 'dashboard.view'],
            ],
        ];

        $roleModels = [];
        foreach ($roles as $name => $meta) {
            $roleModels[$name] = Role::updateOrCreate(
                ['name' => $name],
                [
                    'display_name' => $meta['display_name'],
                    'description' => $meta['description'],
                    'permissions' => $meta['permissions'],
                ]
            );
            // Also ensure legacy uppercase alias exists
            Role::firstOrCreate(
                ['name' => strtoupper($name)],
                [
                    'display_name' => $meta['display_name'],
                    'description' => $meta['description'],
                    'permissions' => $meta['permissions'],
                ]
            );
        }

        // 2. BRANCHES (HQ CENTRAL + 3 RETAIL OUTLETS)
        $branchesData = [
            'HQ-CENTRAL' => [
                'name' => 'Kafeina Central Roastery & Lab Hub',
                'type' => 'CENTRAL_ROASTERY',
                'address' => 'Kawasan Industri Cilandak Commercial Estate Kav. 12, Jakarta Selatan',
                'phone' => '021-7890123',
                'is_active' => true,
            ],
            'OUT-SENO' => [
                'name' => 'Kafeina Flagship Senopati',
                'type' => 'RETAIL_BRANCH',
                'address' => 'Jl. Senopati No. 44, Kebayoran Baru, Jakarta Selatan',
                'phone' => '021-5234567',
                'is_active' => true,
            ],
            'OUT-BRG' => [
                'name' => 'Kafeina Specialty Braga Bandung',
                'type' => 'RETAIL_BRANCH',
                'address' => 'Jl. Braga No. 88, Sumur Bandung, Kota Bandung',
                'phone' => '022-4239876',
                'is_active' => true,
            ],
            'OUT-MLO' => [
                'name' => 'Kafeina Heritage Malioboro Jogja',
                'type' => 'RETAIL_BRANCH',
                'address' => 'Jl. Malioboro No. 120, Sosromenduran, Gedong Tengen, Yogyakarta',
                'phone' => '0274-567890',
                'is_active' => true,
            ],
        ];

        $branchModels = [];
        foreach ($branchesData as $code => $data) {
            $branchModels[$code] = Branch::updateOrCreate(['code' => $code], $data);
        }

        // 3. WAREHOUSES
        $warehousesData = [
            'WH-RAW-HQ' => ['branch_id' => $branchModels['HQ-CENTRAL']->id, 'name' => 'Gudang Biji Mentah Sentral (Central Raw)', 'type' => 'CENTRAL_RAW'],
            'WH-ROAST-HQ' => ['branch_id' => $branchModels['HQ-CENTRAL']->id, 'name' => 'Gudang Biji Sangrai Sentral (Central Roasted)', 'type' => 'CENTRAL_ROASTED'],
            'WH-TRANSIT' => ['branch_id' => $branchModels['HQ-CENTRAL']->id, 'name' => 'Akun Virtual In-Transit Ekspedisi', 'type' => 'TRANSIT'],
            'WH-BAR-SENO' => ['branch_id' => $branchModels['OUT-SENO']->id, 'name' => 'Stok Bar Aktif Flagship Senopati', 'type' => 'RETAIL_BAR'],
            'WH-BAR-BRG' => ['branch_id' => $branchModels['OUT-BRG']->id, 'name' => 'Stok Bar Aktif Braga Bandung', 'type' => 'RETAIL_BAR'],
            'WH-BAR-MLO' => ['branch_id' => $branchModels['OUT-MLO']->id, 'name' => 'Stok Bar Aktif Malioboro Jogja', 'type' => 'RETAIL_BAR'],
        ];

        $whModels = [];
        foreach ($warehousesData as $code => $data) {
            $whModels[$code] = InventoryWarehouse::updateOrCreate(['code' => $code], $data);
        }

        // 4. USERS & OPERATIONAL ACCOUNTS
        $usersData = [
            'admin@kafeina.id' => [
                'name' => 'Dimas Wicaksono (CEO & Founder)',
                'role_id' => $roleModels['super_admin']->id,
                'branch_id' => $branchModels['HQ-CENTRAL']->id,
                'password' => Hash::make('password123'),
                'pin_code' => Hash::make('111111'),
                'is_active' => true,
            ],
            'procurement@kafeina.id' => [
                'name' => 'Anisa Rahmawati (Head of Procurement)',
                'role_id' => $roleModels['procurement_officer']->id,
                'branch_id' => $branchModels['HQ-CENTRAL']->id,
                'password' => Hash::make('password123'),
                'pin_code' => Hash::make('222222'),
                'is_active' => true,
            ],
            'qc@kafeina.id' => [
                'name' => 'Fajar Nugraha (Licensed Q-Grader)',
                'role_id' => $roleModels['qc_inspector']->id,
                'branch_id' => $branchModels['HQ-CENTRAL']->id,
                'password' => Hash::make('password123'),
                'pin_code' => Hash::make('333333'),
                'is_active' => true,
            ],
            'roaster@kafeina.id' => [
                'name' => 'Eko Prasetyo (Master Roaster)',
                'role_id' => $roleModels['head_roaster']->id,
                'branch_id' => $branchModels['HQ-CENTRAL']->id,
                'password' => Hash::make('password123'),
                'pin_code' => Hash::make('444444'),
                'is_active' => true,
            ],
            'warehouse@kafeina.id' => [
                'name' => 'Bambang Supriyadi (Logistics Supervisor)',
                'role_id' => $roleModels['warehouse_staff']->id,
                'branch_id' => $branchModels['HQ-CENTRAL']->id,
                'password' => Hash::make('password123'),
                'pin_code' => Hash::make('555555'),
                'is_active' => true,
            ],
            'cashier.senopati@kafeina.id' => [
                'name' => 'Rizky Ramadhan (Head Barista Senopati)',
                'role_id' => $roleModels['branch_cashier']->id,
                'branch_id' => $branchModels['OUT-SENO']->id,
                'password' => Hash::make('password123'),
                'pin_code' => Hash::make('123456'),
                'is_active' => true,
            ],
            'cashier.braga@kafeina.id' => [
                'name' => 'Dinda Kirana (Senior Barista Braga)',
                'role_id' => $roleModels['branch_cashier']->id,
                'branch_id' => $branchModels['OUT-BRG']->id,
                'password' => Hash::make('password123'),
                'pin_code' => Hash::make('123456'),
                'is_active' => true,
            ],
            'cashier.malioboro@kafeina.id' => [
                'name' => 'Bagus Wicaksono (Head Barista Malioboro)',
                'role_id' => $roleModels['branch_cashier']->id,
                'branch_id' => $branchModels['OUT-MLO']->id,
                'password' => Hash::make('password123'),
                'pin_code' => Hash::make('123456'),
                'is_active' => true,
            ],
            'supervisor.senopati@kafeina.id' => [
                'name' => 'Hendro Santoso (Store Supervisor Senopati)',
                'role_id' => $roleModels['supervisor']->id,
                'branch_id' => $branchModels['OUT-SENO']->id,
                'password' => Hash::make('password123'),
                'pin_code' => Hash::make('889900'),
                'is_active' => true,
            ],
        ];

        $userModels = [];
        foreach ($usersData as $email => $data) {
            $userModels[$email] = User::updateOrCreate(['email' => $email], $data);
        }

        // 5. SUPPLIERS (6 PETANI MITRA KOPI SPESIALTI TERKENAL INDONESIA)
        $suppliersData = [
            'SUP-GYO-01' => [
                'name' => 'Koperasi Tani Dataran Tinggi Gayo Pantan Musara',
                'contact_person' => 'Aman Rusdi',
                'phone' => '0811-6812-3456',
                'address' => 'Desa Pantan Musara, Pegasing, Takengon, Aceh Tengah',
                'farm_location' => 'Gayo Highlands (Burni Telong)',
                'altitude_masl' => 1550,
                'rating_score' => 4.95,
                'is_active' => true,
            ],
            'SUP-IJN-02' => [
                'name' => 'Kelompok Tani Belawan Sempol Lereng Ijen',
                'contact_person' => 'Pak Sugeng Riyadi',
                'phone' => '0812-3456-7890',
                'address' => 'Kawasan Perkebunan Kopi Belawan, Sempol, Bondowoso',
                'farm_location' => 'Gunung Ijen Raung, Jawa Timur',
                'altitude_masl' => 1420,
                'rating_score' => 4.88,
                'is_active' => true,
            ],
            'SUP-FLR-03' => [
                'name' => 'Asosiasi Petani Kopi Wolobobo Bajawa Flores',
                'contact_person' => 'Yohanes Bei',
                'phone' => '0813-9876-5432',
                'address' => 'Lembah Wolobobo, Bajawa, Kabupaten Ngada, NTT',
                'farm_location' => 'Dataran Tinggi Ngada (Gunung Inerie)',
                'altitude_masl' => 1400,
                'rating_score' => 4.85,
                'is_active' => true,
            ],
            'SUP-KNT-04' => [
                'name' => 'Subak Abian Ulian Kintamani Specialty',
                'contact_person' => 'I Made Suarta',
                'phone' => '0819-8765-4321',
                'address' => 'Desa Ulian, Kintamani, Kabupaten Bangli, Bali',
                'farm_location' => 'Lereng Gunung Batur Kintamani',
                'altitude_masl' => 1350,
                'rating_score' => 4.82,
                'is_active' => true,
            ],
            'SUP-TRJ-05' => [
                'name' => 'Koperasi Tani Sapan Minanga Toraja Utara',
                'contact_person' => 'Piter Sambolangi',
                'phone' => '0821-4567-8901',
                'address' => 'Kecamatan Buntu Pepasan, Toraja Utara, Sulawesi Selatan',
                'farm_location' => 'Pegawangan Sapan Minanga',
                'altitude_masl' => 1700,
                'rating_score' => 4.92,
                'is_active' => true,
            ],
            'SUP-KRC-06' => [
                'name' => 'Koerintji Barokah Bersama Agro',
                'contact_person' => 'Triyono Raharjo',
                'phone' => '0822-3344-5566',
                'address' => 'Kayu Aro, Kabupaten Kerinci, Jambi',
                'farm_location' => 'Kaki Gunung Kerinci',
                'altitude_masl' => 1600,
                'rating_score' => 4.90,
                'is_active' => true,
            ],
        ];

        $supplierModels = [];
        foreach ($suppliersData as $code => $data) {
            $supplierModels[$code] = Supplier::updateOrCreate(['code' => $code], $data);
        }

        // 6. PRODUCT CATEGORIES
        $categoriesData = [
            'CAT-GREEN' => ['name' => 'Biji Mentah (Green Beans)', 'description' => 'Biji kopi mentah belum disangrai (raw material grade specialty) per gram.'],
            'CAT-ROAST-BULK' => ['name' => 'Biji Sangrai Curah (Bulk Roasted)', 'description' => 'Biji kopi sangrai untuk pasokan bar outlet cabang dan bahan baku menu.'],
            'CAT-DAIRY' => ['name' => 'Susu & Dairy Products', 'description' => 'Bahan baku susu pasteurisasi, fresh milk, dan plant-based oatmilk.'],
            'CAT-SYRUP' => ['name' => 'Sirup Gourmet & Flavouring', 'description' => 'Sirup gourmet perasa tambahan minuman latte dan artisan mocktails.'],
            'CAT-PKG' => ['name' => 'Bahan Kemasan (Packaging)', 'description' => 'Paper cup hot 8oz, cold cup PET 12oz, standing pouch foil 250g, dan degas valve.'],
            'CAT-BEV-ESP' => ['name' => 'Espresso Based Bar Drinks', 'description' => 'Menu minuman espresso, latte, cappuccino, dan magic.'],
            'CAT-BEV-FILTER' => ['name' => 'Manual Brew Single Origin', 'description' => 'Menu seduh manual V60, Kalita Wave, Aeropress, dan Japanese Iced.'],
            'CAT-RETAIL-BEANS' => ['name' => 'Kopi Kemasan Retail 250g', 'description' => 'Whole beans dalam kemasan pouch 250g bersertifikasi untuk take-home customer.'],
        ];

        $catModels = [];
        foreach ($categoriesData as $key => $data) {
            $catModels[$key] = ProductCategory::firstOrCreate(['name' => $data['name']], ['description' => $data['description']]);
        }

        // 7. PRODUCTS (RAW MATERIALS, PACKAGING & FINISHED GOODS)
        $productsData = [
            // Raw Green Beans (per gram)
            'RAW-GB-GAYO' => ['category_id' => $catModels['CAT-GREEN']->id, 'name' => 'Green Beans Aceh Gayo Honey Process', 'unit' => 'gram', 'cost_price' => 130.00, 'selling_price' => 0.00, 'is_raw_material' => true],
            'RAW-GB-IJEN' => ['category_id' => $catModels['CAT-GREEN']->id, 'name' => 'Green Beans Ijen Anaerobic Natural', 'unit' => 'gram', 'cost_price' => 125.00, 'selling_price' => 0.00, 'is_raw_material' => true],
            'RAW-GB-FLORES' => ['category_id' => $catModels['CAT-GREEN']->id, 'name' => 'Green Beans Flores Bajawa Full Washed', 'unit' => 'gram', 'cost_price' => 120.00, 'selling_price' => 0.00, 'is_raw_material' => true],
            'RAW-GB-KINTA' => ['category_id' => $catModels['CAT-GREEN']->id, 'name' => 'Green Beans Bali Kintamani Carbonic Maceration', 'unit' => 'gram', 'cost_price' => 140.00, 'selling_price' => 0.00, 'is_raw_material' => true],
            'RAW-GB-TORAJA' => ['category_id' => $catModels['CAT-GREEN']->id, 'name' => 'Green Beans Toraja Sapan Wet Hulled Premium', 'unit' => 'gram', 'cost_price' => 135.00, 'selling_price' => 0.00, 'is_raw_material' => true],

            // Roasted Beans Bulk (per gram)
            'RAW-RB-GAYO' => ['category_id' => $catModels['CAT-ROAST-BULK']->id, 'name' => 'Bulk Roasted Arabika Aceh Gayo Honey', 'unit' => 'gram', 'cost_price' => 195.00, 'selling_price' => 0.00, 'is_raw_material' => true],
            'RAW-RB-IJEN' => ['category_id' => $catModels['CAT-ROAST-BULK']->id, 'name' => 'Bulk Roasted Arabika Ijen Anaerobic', 'unit' => 'gram', 'cost_price' => 188.00, 'selling_price' => 0.00, 'is_raw_material' => true],
            'RAW-RB-HOUSE' => ['category_id' => $catModels['CAT-ROAST-BULK']->id, 'name' => 'Bulk Roasted Kafeina House Blend (60% Gayo 40% Ijen)', 'unit' => 'gram', 'cost_price' => 190.00, 'selling_price' => 0.00, 'is_raw_material' => true],

            // Dairy & Syrups
            'RAW-MILK-FRESH' => ['category_id' => $catModels['CAT-DAIRY']->id, 'name' => 'Greenfields Fresh Milk Pasteurisasi', 'unit' => 'ml', 'cost_price' => 25.00, 'selling_price' => 0.00, 'is_raw_material' => true],
            'RAW-MILK-OAT' => ['category_id' => $catModels['CAT-DAIRY']->id, 'name' => 'Oatly Barista Edition Oat Milk', 'unit' => 'ml', 'cost_price' => 50.00, 'selling_price' => 0.00, 'is_raw_material' => true],
            'RAW-SYRUP-VANILLA' => ['category_id' => $catModels['CAT-SYRUP']->id, 'name' => 'Monin Vanilla Syrup Gourmet', 'unit' => 'ml', 'cost_price' => 180.00, 'selling_price' => 0.00, 'is_raw_material' => true],
            'RAW-SYRUP-CARAMEL' => ['category_id' => $catModels['CAT-SYRUP']->id, 'name' => 'Monin Salted Caramel Syrup Gourmet', 'unit' => 'ml', 'cost_price' => 185.00, 'selling_price' => 0.00, 'is_raw_material' => true],

            // Packaging
            'PKG-CUP-HOT8' => ['category_id' => $catModels['CAT-PKG']->id, 'name' => 'Paper Cup Hot 8oz Double Wall + Eco Lid', 'unit' => 'pcs', 'cost_price' => 1200.00, 'selling_price' => 0.00, 'is_raw_material' => true],
            'PKG-CUP-COLD12' => ['category_id' => $catModels['CAT-PKG']->id, 'name' => 'Cold Cup PET 12oz Emboss Logo + Strawless Lid', 'unit' => 'pcs', 'cost_price' => 1400.00, 'selling_price' => 0.00, 'is_raw_material' => true],
            'PKG-POUCH-250G' => ['category_id' => $catModels['CAT-PKG']->id, 'name' => 'Matte Black Gusset Pouch 250g with Degas Valve', 'unit' => 'pcs', 'cost_price' => 3500.00, 'selling_price' => 0.00, 'is_raw_material' => true],
            'PKG-LABEL-250G' => ['category_id' => $catModels['CAT-PKG']->id, 'name' => 'Label Kafeina Specialty 250g Gold Foil Emboss', 'unit' => 'pcs', 'cost_price' => 600.00, 'selling_price' => 0.00, 'is_raw_material' => true],

            // Retail & POS Menu Items (Finished Goods)
            'PRD-ESP-01' => ['category_id' => $catModels['CAT-BEV-ESP']->id, 'name' => 'Single Origin Double Espresso', 'unit' => 'cup', 'cost_price' => 3510.00, 'selling_price' => 28000.00, 'is_raw_material' => false],
            'PRD-ESP-02' => ['category_id' => $catModels['CAT-BEV-ESP']->id, 'name' => 'Caffe Americano Hot/Iced', 'unit' => 'cup', 'cost_price' => 4910.00, 'selling_price' => 35000.00, 'is_raw_material' => false],
            'PRD-ESP-03' => ['category_id' => $catModels['CAT-BEV-ESP']->id, 'name' => 'Flat White Velvet Hot 8oz', 'unit' => 'cup', 'cost_price' => 8510.00, 'selling_price' => 42000.00, 'is_raw_material' => false],
            'PRD-ESP-04' => ['category_id' => $catModels['CAT-BEV-ESP']->id, 'name' => 'Kafeina Magic (Double Ristretto + Silk Milk)', 'unit' => 'cup', 'cost_price' => 8110.00, 'selling_price' => 44000.00, 'is_raw_material' => false],
            'PRD-ESP-05' => ['category_id' => $catModels['CAT-BEV-ESP']->id, 'name' => 'Madagascar Vanilla Artisan Latte', 'unit' => 'cup', 'cost_price' => 12110.00, 'selling_price' => 48000.00, 'is_raw_material' => false],
            'PRD-MB-01' => ['category_id' => $catModels['CAT-BEV-FILTER']->id, 'name' => 'V60 Pour Over Aceh Gayo Honey', 'unit' => 'cup', 'cost_price' => 4710.00, 'selling_price' => 45000.00, 'is_raw_material' => false],
            'PRD-MB-02' => ['category_id' => $catModels['CAT-BEV-FILTER']->id, 'name' => 'V60 Pour Over Ijen Anaerobic Natural', 'unit' => 'cup', 'cost_price' => 4580.00, 'selling_price' => 45000.00, 'is_raw_material' => false],
            'PRD-RET-01' => ['category_id' => $catModels['CAT-RETAIL-BEANS']->id, 'name' => 'Aceh Gayo Honey Specialty Whole Beans 250g', 'unit' => 'pouch', 'cost_price' => 52850.00, 'selling_price' => 135000.00, 'is_raw_material' => false],
            'PRD-RET-02' => ['category_id' => $catModels['CAT-RETAIL-BEANS']->id, 'name' => 'Kafeina Signature House Blend 250g', 'unit' => 'pouch', 'cost_price' => 51600.00, 'selling_price' => 125000.00, 'is_raw_material' => false],
        ];

        $prodModels = [];
        foreach ($productsData as $sku => $data) {
            $prodModels[$sku] = Product::updateOrCreate(
                ['sku' => $sku],
                array_merge($data, ['is_active' => true])
            );
        }

        // 8. PRODUCT RECIPES (FORMULA AUTO-DEDUCT KASIR)
        $recipesData = [
            // Single Origin Espresso: 18g House Beans
            ['product_id' => $prodModels['PRD-ESP-01']->id, 'ingredient_product_id' => $prodModels['RAW-RB-HOUSE']->id, 'quantity_required' => 18.00, 'unit' => 'gram'],

            // Americano: 18g House Beans + 1 Cold Cup
            ['product_id' => $prodModels['PRD-ESP-02']->id, 'ingredient_product_id' => $prodModels['RAW-RB-HOUSE']->id, 'quantity_required' => 18.00, 'unit' => 'gram'],
            ['product_id' => $prodModels['PRD-ESP-02']->id, 'ingredient_product_id' => $prodModels['PKG-CUP-COLD12']->id, 'quantity_required' => 1.00, 'unit' => 'pcs'],

            // Flat White: 18g House Beans + 150ml Fresh Milk + 1 Hot Cup
            ['product_id' => $prodModels['PRD-ESP-03']->id, 'ingredient_product_id' => $prodModels['RAW-RB-HOUSE']->id, 'quantity_required' => 18.00, 'unit' => 'gram'],
            ['product_id' => $prodModels['PRD-ESP-03']->id, 'ingredient_product_id' => $prodModels['RAW-MILK-FRESH']->id, 'quantity_required' => 150.00, 'unit' => 'ml'],
            ['product_id' => $prodModels['PRD-ESP-03']->id, 'ingredient_product_id' => $prodModels['PKG-CUP-HOT8']->id, 'quantity_required' => 1.00, 'unit' => 'pcs'],

            // Magic: 20g Gayo Beans + 130ml Fresh Milk + 1 Hot Cup
            ['product_id' => $prodModels['PRD-ESP-04']->id, 'ingredient_product_id' => $prodModels['RAW-RB-GAYO']->id, 'quantity_required' => 20.00, 'unit' => 'gram'],
            ['product_id' => $prodModels['PRD-ESP-04']->id, 'ingredient_product_id' => $prodModels['RAW-MILK-FRESH']->id, 'quantity_required' => 130.00, 'unit' => 'ml'],
            ['product_id' => $prodModels['PRD-ESP-04']->id, 'ingredient_product_id' => $prodModels['PKG-CUP-HOT8']->id, 'quantity_required' => 1.00, 'unit' => 'pcs'],

            // Vanilla Artisan Latte: 18g House Beans + 180ml Fresh Milk + 20ml Vanilla Syrup + 1 Cold Cup
            ['product_id' => $prodModels['PRD-ESP-05']->id, 'ingredient_product_id' => $prodModels['RAW-RB-HOUSE']->id, 'quantity_required' => 18.00, 'unit' => 'gram'],
            ['product_id' => $prodModels['PRD-ESP-05']->id, 'ingredient_product_id' => $prodModels['RAW-MILK-FRESH']->id, 'quantity_required' => 180.00, 'unit' => 'ml'],
            ['product_id' => $prodModels['PRD-ESP-05']->id, 'ingredient_product_id' => $prodModels['RAW-SYRUP-VANILLA']->id, 'quantity_required' => 20.00, 'unit' => 'ml'],
            ['product_id' => $prodModels['PRD-ESP-05']->id, 'ingredient_product_id' => $prodModels['PKG-CUP-COLD12']->id, 'quantity_required' => 1.00, 'unit' => 'pcs'],

            // V60 Gayo Honey: 15g Gayo Beans + 1 Cold Cup
            ['product_id' => $prodModels['PRD-MB-01']->id, 'ingredient_product_id' => $prodModels['RAW-RB-GAYO']->id, 'quantity_required' => 15.00, 'unit' => 'gram'],
            ['product_id' => $prodModels['PRD-MB-01']->id, 'ingredient_product_id' => $prodModels['PKG-CUP-COLD12']->id, 'quantity_required' => 1.00, 'unit' => 'pcs'],

            // V60 Ijen Anaerobic: 15g Ijen Beans + 1 Cold Cup
            ['product_id' => $prodModels['PRD-MB-02']->id, 'ingredient_product_id' => $prodModels['RAW-RB-IJEN']->id, 'quantity_required' => 15.00, 'unit' => 'gram'],
            ['product_id' => $prodModels['PRD-MB-02']->id, 'ingredient_product_id' => $prodModels['PKG-CUP-COLD12']->id, 'quantity_required' => 1.00, 'unit' => 'pcs'],
        ];

        foreach ($recipesData as $rec) {
            ProductRecipe::firstOrCreate(
                ['product_id' => $rec['product_id'], 'ingredient_product_id' => $rec['ingredient_product_id']],
                ['quantity_required' => $rec['quantity_required'], 'unit' => $rec['unit']]
            );
        }

        // 9. BOM (BILL OF MATERIALS) UNTUK PRODUK RETAIL KEMASAN 250G
        $bomRetailGayo = Bom::updateOrCreate(
            ['product_id' => $prodModels['PRD-RET-01']->id],
            ['name' => 'Kemasan 250g Retail Gayo Honey', 'description' => 'Formula kemasan retail pouch valve nitrogen flush']
        );

        BomItem::updateOrCreate(['bom_id' => $bomRetailGayo->id, 'material_product_id' => $prodModels['RAW-RB-GAYO']->id], ['quantity_required' => 250.00, 'unit' => 'gram']);
        BomItem::updateOrCreate(['bom_id' => $bomRetailGayo->id, 'material_product_id' => $prodModels['PKG-POUCH-250G']->id], ['quantity_required' => 1.00, 'unit' => 'pcs']);
        BomItem::updateOrCreate(['bom_id' => $bomRetailGayo->id, 'material_product_id' => $prodModels['PKG-LABEL-250G']->id], ['quantity_required' => 1.00, 'unit' => 'pcs']);

        // 10. ROASTING PROFILES (STANDAR GIESEN W15A ROASTER SENTRAL)
        $profilesData = [
            'PROF-LIGHT-GYO' => [
                'name' => 'Nordic Light Roast Filter (Gayo Honey)',
                'roast_level' => 'LIGHT',
                'charge_temp_celsius' => 195.00,
                'turning_point_celsius' => 92.00,
                'development_time_ratio' => 13.50,
                'target_air_flow' => 'Airflow 120 Pa / Drum 55 Hz',
            ],
            'PROF-MED-OMNI' => [
                'name' => 'Omniroast Medium Roast (Ijen Natural)',
                'roast_level' => 'MEDIUM',
                'charge_temp_celsius' => 205.00,
                'turning_point_celsius' => 98.00,
                'development_time_ratio' => 15.80,
                'target_air_flow' => 'Airflow 130 Pa / Drum 52 Hz',
            ],
            'PROF-MEDDARK-HOUSE' => [
                'name' => 'Full Medium Roast Kafeina Espresso Blend',
                'roast_level' => 'MEDIUM_DARK',
                'charge_temp_celsius' => 212.00,
                'turning_point_celsius' => 104.00,
                'development_time_ratio' => 18.20,
                'target_air_flow' => 'Airflow 140 Pa / Drum 50 Hz',
            ],
        ];

        $profileModels = [];
        foreach ($profilesData as $code => $data) {
            $profileModels[$code] = RoastingProfile::updateOrCreate(['profile_code' => $code], $data);
        }

        // 11. PURCHASE ORDERS & GREEN BEAN LOTS & QC LAB
        // PO 1: Takengon Gayo
        $poGayo = PurchaseOrder::updateOrCreate(
            ['po_number' => 'PO-202611-001'],
            [
                'supplier_id' => $supplierModels['SUP-GYO-01']->id,
                'branch_id' => $branchModels['HQ-CENTRAL']->id,
                'user_id' => $userModels['procurement@kafeina.id']->id,
                'order_date' => Carbon::now()->subDays(10),
                'expected_delivery_date' => Carbon::now()->subDays(6),
                'total_amount' => 39000000.00, // 300kg @ 130k
                'status' => 'COMPLETED',
            ]
        );

        PurchaseOrderItem::updateOrCreate(
            ['purchase_order_id' => $poGayo->id, 'product_name' => 'Green Beans Aceh Gayo Honey Process'],
            ['quantity_kg' => 300.00, 'unit_price' => 130000.00, 'subtotal' => 39000000.00]
        );

        $lotGayo = GreenBeanLot::updateOrCreate(
            ['lot_number' => 'GB-GYO-2026-0001'],
            [
                'purchase_order_id' => $poGayo->id,
                'variety' => 'Arabica Gayo 1 & Bourbon',
                'origin_region' => 'Pantan Musara, Aceh Tengah',
                'process_type' => 'HONEY',
                'harvest_year' => 2026,
                'initial_weight_grams' => 300000.00,
                'current_weight_grams' => 250000.00, // 50kg roasted
                'price_per_gram' => 130.00,
            ]
        );

        QualityControl::updateOrCreate(
            ['lot_id' => $lotGayo->id],
            [
                'inspector_id' => $userModels['qc@kafeina.id']->id,
                'inspection_date' => Carbon::now()->subDays(6),
                'moisture_content' => 11.20,
                'bulk_density_g_l' => 715.00,
                'primary_defects' => 0,
                'secondary_defects' => 2,
                'sca_cupping_score' => 87.50,
                'qc_status' => 'ACCEPTED',
                'notes' => 'Aroma jasmine & apricot sangat menonjol, acidity crisp malic, body clean sweet honey finish. Lolos standar spesialti SCAA.',
            ]
        );

        // PO 2: Ijen Bondowoso
        $poIjen = PurchaseOrder::updateOrCreate(
            ['po_number' => 'PO-202611-002'],
            [
                'supplier_id' => $supplierModels['SUP-IJN-02']->id,
                'branch_id' => $branchModels['HQ-CENTRAL']->id,
                'user_id' => $userModels['procurement@kafeina.id']->id,
                'order_date' => Carbon::now()->subDays(8),
                'expected_delivery_date' => Carbon::now()->subDays(4),
                'total_amount' => 25000000.00, // 200kg @ 125k
                'status' => 'COMPLETED',
            ]
        );

        PurchaseOrderItem::updateOrCreate(
            ['purchase_order_id' => $poIjen->id, 'product_name' => 'Green Beans Ijen Anaerobic Natural'],
            ['quantity_kg' => 200.00, 'unit_price' => 125000.00, 'subtotal' => 25000000.00]
        );

        $lotIjen = GreenBeanLot::updateOrCreate(
            ['lot_number' => 'GB-IJN-2026-0002'],
            [
                'purchase_order_id' => $poIjen->id,
                'variety' => 'Arabica Kartika & USDA',
                'origin_region' => 'Belawan Sempol, Ijen Bondowoso',
                'process_type' => 'NATURAL',
                'harvest_year' => 2026,
                'initial_weight_grams' => 200000.00,
                'current_weight_grams' => 160000.00, // 40kg roasted
                'price_per_gram' => 125.00,
            ]
        );

        QualityControl::updateOrCreate(
            ['lot_id' => $lotIjen->id],
            [
                'inspector_id' => $userModels['qc@kafeina.id']->id,
                'inspection_date' => Carbon::now()->subDays(4),
                'moisture_content' => 11.50,
                'bulk_density_g_l' => 698.00,
                'primary_defects' => 0,
                'secondary_defects' => 4,
                'sca_cupping_score' => 86.25,
                'qc_status' => 'ACCEPTED',
                'notes' => 'Ripe strawberry, fermented cacao nibs, syrupy mouthfeel. Bebas cacat primer.',
            ]
        );

        // 12. INITIAL WAREHOUSE STOCKS & BALANCES (MULTI-BRANCH CANONICAL)
        // Central Raw Stocks (Silo Green Beans)
        $rawStocks = [
            'RAW-GB-GAYO' => 350000.00, // 350kg
            'RAW-GB-IJEN' => 280000.00, // 280kg
            'RAW-GB-FLORES' => 200000.00, // 200kg
            'RAW-GB-KINTA' => 150000.00, // 150kg
            'RAW-GB-TORAJA' => 150000.00, // 150kg
        ];
        foreach ($rawStocks as $sku => $qty) {
            WarehouseStock::updateOrCreate(
                ['warehouse_id' => $whModels['WH-RAW-HQ']->id, 'product_id' => $prodModels[$sku]->id],
                ['quantity' => $qty, 'reserved_quantity' => 0, 'safety_stock_threshold' => 100000.00]
            );
        }

        // Central Roasted Stocks & Master Packaging
        $roastedStocks = [
            'RAW-RB-HOUSE' => ['qty' => 45000.00, 'rop' => 15000.00], // 45kg
            'RAW-RB-GAYO' => ['qty' => 25000.00, 'rop' => 10000.00],  // 25kg
            'RAW-RB-IJEN' => ['qty' => 18000.00, 'rop' => 8000.00],   // 18kg
            'RAW-MILK-FRESH' => ['qty' => 50000.00, 'rop' => 20000.00], // 50L buffer
            'RAW-SYRUP-VANILLA' => ['qty' => 10000.00, 'rop' => 3000.00], // 10L buffer
            'PKG-CUP-HOT8' => ['qty' => 2000.00, 'rop' => 500.00],
            'PKG-CUP-COLD12' => ['qty' => 2500.00, 'rop' => 600.00],
            'PKG-POUCH-250G' => ['qty' => 1200.00, 'rop' => 300.00],
            'PKG-LABEL-250G' => ['qty' => 2500.00, 'rop' => 500.00],
            'PRD-RET-01' => ['qty' => 60.00, 'rop' => 15.00],
            'PRD-RET-02' => ['qty' => 80.00, 'rop' => 20.00],
        ];
        foreach ($roastedStocks as $sku => $meta) {
            WarehouseStock::updateOrCreate(
                ['warehouse_id' => $whModels['WH-ROAST-HQ']->id, 'product_id' => $prodModels[$sku]->id],
                ['quantity' => $meta['qty'], 'reserved_quantity' => 0, 'safety_stock_threshold' => $meta['rop']]
            );
        }

        // Branch Bar Stocks Generator for the 3 Retail Outlets
        $barStockTemplate = [
            'OUT-SENO' => [
                'wh' => 'WH-BAR-SENO',
                'stocks' => [
                    'RAW-RB-HOUSE' => ['qty' => 8500.00, 'rop' => 3000.00], // 8.5kg
                    'RAW-RB-GAYO' => ['qty' => 4500.00, 'rop' => 2000.00],  // 4.5kg
                    'RAW-MILK-FRESH' => ['qty' => 48000.00, 'rop' => 15000.00], // 48L
                    'RAW-SYRUP-VANILLA' => ['qty' => 5000.00, 'rop' => 1500.00], // 5L
                    'PKG-CUP-COLD12' => ['qty' => 450.00, 'rop' => 150.00],
                    'PKG-CUP-HOT8' => ['qty' => 320.00, 'rop' => 100.00],
                    'PRD-RET-01' => ['qty' => 15.00, 'rop' => 5.00],
                    'PRD-RET-02' => ['qty' => 20.00, 'rop' => 5.00],
                ],
            ],
            'OUT-BRG' => [
                'wh' => 'WH-BAR-BRG',
                'stocks' => [
                    'RAW-RB-HOUSE' => ['qty' => 6500.00, 'rop' => 3000.00], // 6.5kg
                    'RAW-RB-GAYO' => ['qty' => 3500.00, 'rop' => 2000.00],  // 3.5kg
                    'RAW-MILK-FRESH' => ['qty' => 35000.00, 'rop' => 15000.00], // 35L
                    'RAW-SYRUP-VANILLA' => ['qty' => 4000.00, 'rop' => 1500.00], // 4L
                    'PKG-CUP-COLD12' => ['qty' => 350.00, 'rop' => 150.00],
                    'PKG-CUP-HOT8' => ['qty' => 280.00, 'rop' => 100.00],
                    'PRD-RET-01' => ['qty' => 12.00, 'rop' => 5.00],
                    'PRD-RET-02' => ['qty' => 15.00, 'rop' => 5.00],
                ],
            ],
            'OUT-MLO' => [
                'wh' => 'WH-BAR-MLO',
                'stocks' => [
                    'RAW-RB-HOUSE' => ['qty' => 5500.00, 'rop' => 3000.00], // 5.5kg
                    'RAW-RB-GAYO' => ['qty' => 3000.00, 'rop' => 2000.00],  // 3.0kg
                    'RAW-MILK-FRESH' => ['qty' => 30000.00, 'rop' => 15000.00], // 30L
                    'RAW-SYRUP-VANILLA' => ['qty' => 3000.00, 'rop' => 1500.00], // 3L
                    'PKG-CUP-COLD12' => ['qty' => 300.00, 'rop' => 150.00],
                    'PKG-CUP-HOT8' => ['qty' => 250.00, 'rop' => 100.00],
                    'PRD-RET-01' => ['qty' => 10.00, 'rop' => 5.00],
                    'PRD-RET-02' => ['qty' => 12.00, 'rop' => 5.00],
                ],
            ],
        ];

        foreach ($barStockTemplate as $branchCode => $barData) {
            $whId = $whModels[$barData['wh']]->id;
            foreach ($barData['stocks'] as $sku => $meta) {
                WarehouseStock::updateOrCreate(
                    ['warehouse_id' => $whId, 'product_id' => $prodModels[$sku]->id],
                    ['quantity' => $meta['qty'], 'reserved_quantity' => 0, 'safety_stock_threshold' => $meta['rop']]
                );
            }
        }

        // 13. WORK ORDERS (BATCH SANGRAI MAKE-TO-STOCK RIIL)
        // Batch 1: Completed Gayo Honey Batch
        $woGayo = WorkOrder::updateOrCreate(
            ['wo_number' => 'WO-202611-001'],
            [
                'lot_id' => $lotGayo->id,
                'profile_id' => $profileModels['PROF-LIGHT-GYO']->id,
                'roaster_id' => $userModels['roaster@kafeina.id']->id,
                'green_weight_grams' => 15000.00, // 15kg
                'roasted_weight_grams' => 12720.00, // 12.72kg
                'yield_loss_percentage' => 15.20, // 15.20% (Normal: 14-18%)
                'status' => 'COMPLETED',
                'roast_start_time' => Carbon::now()->subDays(3)->setTime(9, 15),
                'roast_end_time' => Carbon::now()->subDays(3)->setTime(9, 27),
                'notes' => 'Batch Gayo Honey First Crack di 196°C menit 9:45. Drop temp 208°C. Susut 15.20% sempurna. HPP Riil: Rp 195/gram.',
            ]
        );

        // Batch 2: Completed House Blend Batch
        $woHouse = WorkOrder::updateOrCreate(
            ['wo_number' => 'WO-202611-002'],
            [
                'lot_id' => $lotIjen->id,
                'profile_id' => $profileModels['PROF-MEDDARK-HOUSE']->id,
                'roaster_id' => $userModels['roaster@kafeina.id']->id,
                'green_weight_grams' => 20000.00, // 20kg
                'roasted_weight_grams' => 16740.00, // 16.74kg
                'yield_loss_percentage' => 16.30, // 16.30%
                'status' => 'COMPLETED',
                'roast_start_time' => Carbon::now()->subDays(2)->setTime(13, 0),
                'roast_end_time' => Carbon::now()->subDays(2)->setTime(13, 14),
                'notes' => 'Batch Espresso Blend drop saat rolling second crack. HPP Riil: Rp 188/gram.',
            ]
        );

        // Batch 3: Scheduled Batch
        WorkOrder::updateOrCreate(
            ['wo_number' => 'WO-202611-003'],
            [
                'lot_id' => $lotGayo->id,
                'profile_id' => $profileModels['PROF-LIGHT-GYO']->id,
                'roaster_id' => $userModels['roaster@kafeina.id']->id,
                'green_weight_grams' => 25000.00,
                'roasted_weight_grams' => null,
                'yield_loss_percentage' => null,
                'status' => 'SCHEDULED',
                'roast_start_time' => null,
                'roast_end_time' => null,
                'notes' => 'Persiapan stok akhir pekan outlet Senopati & Braga.',
            ]
        );

        // 14. MATERIAL REQUEST & SURAT JALAN TRANSFER (MULTI-BRANCH CANONICAL)
        // MR 1: Senopati (Received / Completed)
        $mrSeno = MaterialRequest::updateOrCreate(
            ['request_number' => 'MR-202611-001'],
            [
                'from_branch_id' => $branchModels['OUT-SENO']->id,
                'to_branch_id' => $branchModels['HQ-CENTRAL']->id,
                'requested_by_user_id' => $userModels['cashier.senopati@kafeina.id']->id,
                'status' => 'RECEIVED',
                'notes' => 'Permintaan stok reguler roasted beans & fresh milk akhir pekan',
            ]
        );

        MaterialRequestItem::updateOrCreate(
            ['material_request_id' => $mrSeno->id, 'product_id' => $prodModels['RAW-RB-HOUSE']->id],
            ['quantity_requested' => 10000.00, 'quantity_fulfilled' => 10000.00]
        );

        $dnSeno = DeliveryNote::updateOrCreate(
            ['delivery_number' => 'SJ-202611-001'],
            [
                'request_id' => $mrSeno->id,
                'source_warehouse_id' => $whModels['WH-ROAST-HQ']->id,
                'dest_warehouse_id' => $whModels['WH-BAR-SENO']->id,
                'dispatched_by_user_id' => $userModels['warehouse@kafeina.id']->id,
                'courier_name' => 'Armada Ekspedisi Internal Kafeina (Van B 1234 KAF)',
                'tracking_code' => 'TRK-KAF-SENO-9821',
                'status' => 'DELIVERED',
                'notes' => 'Diterima utuh tanpa selisih di Senopati (100% verifikasi cocok).',
            ]
        );

        DeliveryNoteItem::updateOrCreate(
            ['delivery_note_id' => $dnSeno->id, 'product_id' => $prodModels['RAW-RB-HOUSE']->id],
            ['quantity_sent' => 10000.00, 'quantity_received' => 10000.00, 'discrepancy_notes' => null]
        );

        // MR 2: Braga Bandung (In-Transit / Dispatched)
        $mrBraga = MaterialRequest::updateOrCreate(
            ['request_number' => 'MR-202611-002'],
            [
                'from_branch_id' => $branchModels['OUT-BRG']->id,
                'to_branch_id' => $branchModels['HQ-CENTRAL']->id,
                'requested_by_user_id' => $userModels['cashier.braga@kafeina.id']->id,
                'status' => 'IN_TRANSIT',
                'notes' => 'Permintaan restock biji sangrai House Blend dan sirup vanilla',
            ]
        );

        MaterialRequestItem::updateOrCreate(
            ['material_request_id' => $mrBraga->id, 'product_id' => $prodModels['RAW-RB-HOUSE']->id],
            ['quantity_requested' => 5000.00, 'quantity_fulfilled' => 5000.00]
        );

        $dnBraga = DeliveryNote::updateOrCreate(
            ['delivery_number' => 'SJ-202611-002'],
            [
                'request_id' => $mrBraga->id,
                'source_warehouse_id' => $whModels['WH-ROAST-HQ']->id,
                'dest_warehouse_id' => $whModels['WH-TRANSIT']->id,
                'dispatched_by_user_id' => $userModels['warehouse@kafeina.id']->id,
                'courier_name' => 'Ekspedisi Intercity Box Pendingin (D 8812 KAF)',
                'tracking_code' => 'TRK-KAF-BRG-4412',
                'status' => 'DISPATCHED',
                'notes' => 'Muatan dalam perjalanan rute tol Jakarta-Bandung.',
            ]
        );

        DeliveryNoteItem::updateOrCreate(
            ['delivery_note_id' => $dnBraga->id, 'product_id' => $prodModels['RAW-RB-HOUSE']->id],
            ['quantity_sent' => 5000.00, 'quantity_received' => 0.00, 'discrepancy_notes' => null]
        );

        // MR 3: Malioboro Jogja (Pending Request)
        $mrMalioboro = MaterialRequest::updateOrCreate(
            ['request_number' => 'MR-202611-003'],
            [
                'from_branch_id' => $branchModels['OUT-MLO']->id,
                'to_branch_id' => $branchModels['HQ-CENTRAL']->id,
                'requested_by_user_id' => $userModels['cashier.malioboro@kafeina.id']->id,
                'status' => 'PENDING',
                'notes' => 'Pengajuan pasokan awal minggu biji sangrai dan kemasan pouch take-home',
            ]
        );

        MaterialRequestItem::updateOrCreate(
            ['material_request_id' => $mrMalioboro->id, 'product_id' => $prodModels['RAW-RB-GAYO']->id],
            ['quantity_requested' => 4000.00, 'quantity_fulfilled' => 0.00]
        );

        // 15. POS SHIFTS & REAL TRANSACTIONS MULTI-CABANG
        $shiftSenopati = PosShift::updateOrCreate(
            ['shift_code' => 'SHIFT-SENO-'.date('Ymd').'-01'],
            [
                'branch_id' => $branchModels['OUT-SENO']->id,
                'cashier_user_id' => $userModels['cashier.senopati@kafeina.id']->id,
                'status' => 'OPEN',
                'opened_at' => Carbon::now()->setTime(7, 30),
                'opening_cash' => 500000.00,
                'closing_cash_actual' => null,
                'closing_cash_system' => null,
                'cash_difference' => 0.00,
            ]
        );

        $shiftBraga = PosShift::updateOrCreate(
            ['shift_code' => 'SHIFT-BRG-'.date('Ymd').'-01'],
            [
                'branch_id' => $branchModels['OUT-BRG']->id,
                'cashier_user_id' => $userModels['cashier.braga@kafeina.id']->id,
                'status' => 'OPEN',
                'opened_at' => Carbon::now()->setTime(8, 0),
                'opening_cash' => 500000.00,
                'closing_cash_actual' => null,
                'closing_cash_system' => null,
                'cash_difference' => 0.00,
            ]
        );

        $shiftMalioboro = PosShift::updateOrCreate(
            ['shift_code' => 'SHIFT-MLO-'.date('Ymd').'-01'],
            [
                'branch_id' => $branchModels['OUT-MLO']->id,
                'cashier_user_id' => $userModels['cashier.malioboro@kafeina.id']->id,
                'status' => 'OPEN',
                'opened_at' => Carbon::now()->setTime(8, 15),
                'opening_cash' => 500000.00,
                'closing_cash_actual' => null,
                'closing_cash_system' => null,
                'cash_difference' => 0.00,
            ]
        );

        // 15.1 UPSTREAM MUTASI STOK (Buku Mutasi Roastery & Logistik)
        $whRaw = $whModels['WH-RAW-HQ'];
        $whRoast = $whModels['WH-ROAST-HQ'];
        $whBarSeno = $whModels['WH-BAR-SENO'];
        $whBarBrg = $whModels['WH-BAR-BRG'];
        $whBarMlo = $whModels['WH-BAR-MLO'];

        // PO Receipts (Penerimaan Green Beans Supplier)
        StockCard::create([
            'warehouse_id' => $whRaw->id,
            'product_id' => $prodModels['RAW-GB-GAYO']->id,
            'lot_id' => 1,
            'reference_type' => 'PO_RECEIPT',
            'reference_id' => 1,
            'quantity_in' => 600.00,
            'quantity_out' => 0.00,
            'balance_after' => 600.00,
            'cost_per_unit' => (float) $prodModels['RAW-GB-GAYO']->cost_price,
            'notes' => 'Penerimaan PO-20261108-001 Koperasi Baitul Qiradh Gayo (600 kg GrainPro)',
            'created_at' => Carbon::now()->subDays(3)->setTime(9, 15),
        ]);

        StockCard::create([
            'warehouse_id' => $whRaw->id,
            'product_id' => $prodModels['RAW-GB-IJEN']->id,
            'lot_id' => 2,
            'reference_type' => 'PO_RECEIPT',
            'reference_id' => 2,
            'quantity_in' => 400.00,
            'quantity_out' => 0.00,
            'balance_after' => 400.00,
            'cost_per_unit' => (float) $prodModels['RAW-GB-IJEN']->cost_price,
            'notes' => 'Penerimaan PO-20261108-002 Paguyuban Tani Ijen Bondowoso (400 kg)',
            'created_at' => Carbon::now()->subDays(3)->setTime(11, 30),
        ]);

        // Work Order Roasting Charges & Outputs
        StockCard::create([
            'warehouse_id' => $whRaw->id,
            'product_id' => $prodModels['RAW-GB-GAYO']->id,
            'lot_id' => 1,
            'reference_type' => 'ROAST_CHARGE',
            'reference_id' => 1,
            'quantity_in' => 0.00,
            'quantity_out' => 15.00,
            'balance_after' => 585.00,
            'cost_per_unit' => (float) $prodModels['RAW-GB-GAYO']->cost_price,
            'notes' => 'Charge Roasting Mesin Giesen W15A Batch #WO-20261108-001',
            'created_at' => Carbon::now()->subDays(2)->setTime(8, 30),
        ]);

        StockCard::create([
            'warehouse_id' => $whRoast->id,
            'product_id' => $prodModels['RAW-RB-GAYO']->id,
            'lot_id' => null,
            'reference_type' => 'ROAST_OUTPUT',
            'reference_id' => 1,
            'quantity_in' => 12.65,
            'quantity_out' => 0.00,
            'balance_after' => 12.65,
            'cost_per_unit' => 135000.00,
            'notes' => 'Output Roasting Biji Matang Gayo Honey (Yield 84.3%, Susut 15.7%)',
            'created_at' => Carbon::now()->subDays(2)->setTime(9, 0),
        ]);

        StockCard::create([
            'warehouse_id' => $whRaw->id,
            'product_id' => $prodModels['RAW-GB-IJEN']->id,
            'lot_id' => 2,
            'reference_type' => 'ROAST_CHARGE',
            'reference_id' => 2,
            'quantity_in' => 0.00,
            'quantity_out' => 15.00,
            'balance_after' => 385.00,
            'cost_per_unit' => (float) $prodModels['RAW-GB-IJEN']->cost_price,
            'notes' => 'Charge Roasting Mesin Giesen W15A Batch #WO-20261108-002',
            'created_at' => Carbon::now()->subDays(2)->setTime(10, 0),
        ]);

        StockCard::create([
            'warehouse_id' => $whRoast->id,
            'product_id' => $prodModels['RAW-RB-IJEN']->id,
            'lot_id' => null,
            'reference_type' => 'ROAST_OUTPUT',
            'reference_id' => 2,
            'quantity_in' => 12.70,
            'quantity_out' => 0.00,
            'balance_after' => 12.70,
            'cost_per_unit' => 148000.00,
            'notes' => 'Output Roasting Biji Matang Ijen Anaerobic (Yield 84.7%, Susut 15.3%)',
            'created_at' => Carbon::now()->subDays(2)->setTime(10, 30),
        ]);

        // Transfer Antar Gudang Cabang (Surat Jalan Logistik)
        StockCard::create([
            'warehouse_id' => $whRoast->id,
            'product_id' => $prodModels['RAW-RB-HOUSE']->id,
            'lot_id' => null,
            'reference_type' => 'TRANSFER_DISPATCH',
            'reference_id' => 1,
            'quantity_in' => 0.00,
            'quantity_out' => 6.00,
            'balance_after' => 18.00,
            'cost_per_unit' => (float) $prodModels['RAW-RB-HOUSE']->cost_price,
            'notes' => 'Pengiriman Surat Jalan SJ-202611-001 ke Bar Braga Bandung',
            'created_at' => Carbon::now()->subDays(1)->setTime(13, 0),
        ]);

        StockCard::create([
            'warehouse_id' => $whBarBrg->id,
            'product_id' => $prodModels['RAW-RB-HOUSE']->id,
            'lot_id' => null,
            'reference_type' => 'TRANSFER_RECEIVE',
            'reference_id' => 1,
            'quantity_in' => 6.00,
            'quantity_out' => 0.00,
            'balance_after' => 16.00,
            'cost_per_unit' => (float) $prodModels['RAW-RB-HOUSE']->cost_price,
            'notes' => 'Penerimaan Pasokan Bar Braga dari Sentral Roastery SJ-202611-001',
            'created_at' => Carbon::now()->subDays(1)->setTime(16, 45),
        ]);

        // 15.2 TRANSAKSI PENJUALAN PER CABANG & MUTASI POS
        $branchesOrdersConfig = [
            // CABANG 1: SENOPATI
            [
                'branch' => $branchModels['OUT-SENO'],
                'shift' => $shiftSenopati,
                'cashier' => $userModels['cashier.senopati@kafeina.id'],
                'warehouse' => $whBarSeno,
                'prefix' => 'SENO',
                'orders' => [
                    ['customer' => 'Budi Santoso', 'table' => '04', 'type' => 'DINE_IN', 'pay' => 'QRIS_MIDTRANS', 'ref' => 'qris-mpm-seno-001', 'items' => [['sku' => 'PRD-ESP-04', 'qty' => 2], ['sku' => 'PRD-ESP-03', 'qty' => 1]]],
                    ['customer' => 'Amanda Putri', 'table' => '-', 'type' => 'TAKE_AWAY', 'pay' => 'CASH', 'ref' => null, 'items' => [['sku' => 'PRD-MB-01', 'qty' => 1], ['sku' => 'PRD-RET-01', 'qty' => 1]]],
                    ['customer' => 'dr. Faisal', 'table' => '02', 'type' => 'DINE_IN', 'pay' => 'QRIS_MIDTRANS', 'ref' => 'qris-mpm-seno-003', 'items' => [['sku' => 'PRD-ESP-02', 'qty' => 2], ['sku' => 'PRD-ESP-05', 'qty' => 2]]],
                    ['customer' => 'Kevin Sanjaya', 'table' => '07', 'type' => 'DINE_IN', 'pay' => 'BANK_TRANSFER', 'ref' => 'VA-BCA-98214', 'items' => [['sku' => 'PRD-ESP-01', 'qty' => 2], ['sku' => 'PRD-ESP-03', 'qty' => 1]]],
                    ['customer' => 'Ratna Sari', 'table' => '-', 'type' => 'TAKE_AWAY', 'pay' => 'QRIS_MIDTRANS', 'ref' => 'qris-mpm-seno-005', 'items' => [['sku' => 'PRD-RET-02', 'qty' => 2]]],
                    ['customer' => 'Reza Rahadian', 'table' => '05', 'type' => 'DINE_IN', 'pay' => 'CASH', 'ref' => null, 'items' => [['sku' => 'PRD-ESP-05', 'qty' => 1], ['sku' => 'PRD-ESP-04', 'qty' => 1]]],
                    ['customer' => 'Nia Ramadhani', 'table' => '01', 'type' => 'DINE_IN', 'pay' => 'QRIS_MIDTRANS', 'ref' => 'qris-mpm-seno-007', 'items' => [['sku' => 'PRD-ESP-03', 'qty' => 2], ['sku' => 'PRD-MB-02', 'qty' => 1]]],
                    ['customer' => 'Dimas Anggara', 'table' => '-', 'type' => 'TAKE_AWAY', 'pay' => 'CASH', 'ref' => null, 'items' => [['sku' => 'PRD-ESP-02', 'qty' => 2]]],
                    ['customer' => 'Gita Savitri', 'table' => '03', 'type' => 'DINE_IN', 'pay' => 'QRIS_MIDTRANS', 'ref' => 'qris-mpm-seno-009', 'items' => [['sku' => 'PRD-ESP-04', 'qty' => 1], ['sku' => 'PRD-ESP-05', 'qty' => 1]]],
                ],
                'calib' => ['customer' => 'Barista Senopati (Dial-in)', 'items' => [['sku' => 'PRD-ESP-01', 'qty' => 3]]],
            ],
            // CABANG 2: BRAGA BANDUNG
            [
                'branch' => $branchModels['OUT-BRG'],
                'shift' => $shiftBraga,
                'cashier' => $userModels['cashier.braga@kafeina.id'],
                'warehouse' => $whBarBrg,
                'prefix' => 'BRG',
                'orders' => [
                    ['customer' => 'Ridwan Kamil', 'table' => '02', 'type' => 'DINE_IN', 'pay' => 'QRIS_MIDTRANS', 'ref' => 'qris-mpm-brg-001', 'items' => [['sku' => 'PRD-ESP-03', 'qty' => 2], ['sku' => 'PRD-MB-01', 'qty' => 1]]],
                    ['customer' => 'Annisa Pohan', 'table' => '-', 'type' => 'TAKE_AWAY', 'pay' => 'CASH', 'ref' => null, 'items' => [['sku' => 'PRD-ESP-04', 'qty' => 1], ['sku' => 'PRD-RET-02', 'qty' => 1]]],
                    ['customer' => 'Dedi Mulyadi', 'table' => '06', 'type' => 'DINE_IN', 'pay' => 'QRIS_MIDTRANS', 'ref' => 'qris-mpm-brg-003', 'items' => [['sku' => 'PRD-ESP-01', 'qty' => 2], ['sku' => 'PRD-ESP-02', 'qty' => 1]]],
                    ['customer' => 'Sheila Dara', 'table' => '04', 'type' => 'DINE_IN', 'pay' => 'QRIS_MIDTRANS', 'ref' => 'qris-mpm-brg-004', 'items' => [['sku' => 'PRD-ESP-05', 'qty' => 2], ['sku' => 'PRD-ESP-02', 'qty' => 1]]],
                    ['customer' => 'Vidi Aldiano', 'table' => '-', 'type' => 'TAKE_AWAY', 'pay' => 'CASH', 'ref' => null, 'items' => [['sku' => 'PRD-MB-02', 'qty' => 2]]],
                    ['customer' => 'Tulus', 'table' => '01', 'type' => 'DINE_IN', 'pay' => 'QRIS_MIDTRANS', 'ref' => 'qris-mpm-brg-006', 'items' => [['sku' => 'PRD-ESP-03', 'qty' => 2]]],
                    ['customer' => 'Isyana Sarasvati', 'table' => '03', 'type' => 'DINE_IN', 'pay' => 'BANK_TRANSFER', 'ref' => 'VA-MDR-44321', 'items' => [['sku' => 'PRD-ESP-04', 'qty' => 1], ['sku' => 'PRD-RET-01', 'qty' => 1]]],
                ],
                'calib' => ['customer' => 'Barista Braga (Dial-in)', 'items' => [['sku' => 'PRD-ESP-01', 'qty' => 2]]],
            ],
            // CABANG 3: MALIOBORO JOGJA
            [
                'branch' => $branchModels['OUT-MLO'],
                'shift' => $shiftMalioboro,
                'cashier' => $userModels['cashier.malioboro@kafeina.id'],
                'warehouse' => $whBarMlo,
                'prefix' => 'MLO',
                'orders' => [
                    ['customer' => 'Sultan Hamengku', 'table' => '01', 'type' => 'DINE_IN', 'pay' => 'QRIS_MIDTRANS', 'ref' => 'qris-mpm-mlo-001', 'items' => [['sku' => 'PRD-MB-01', 'qty' => 2], ['sku' => 'PRD-ESP-03', 'qty' => 1]]],
                    ['customer' => 'Dian Sastrowardoyo', 'table' => '03', 'type' => 'DINE_IN', 'pay' => 'QRIS_MIDTRANS', 'ref' => 'qris-mpm-mlo-002', 'items' => [['sku' => 'PRD-ESP-04', 'qty' => 1], ['sku' => 'PRD-ESP-05', 'qty' => 1]]],
                    ['customer' => 'Nicholas Saputra', 'table' => '-', 'type' => 'TAKE_AWAY', 'pay' => 'CASH', 'ref' => null, 'items' => [['sku' => 'PRD-MB-02', 'qty' => 2], ['sku' => 'PRD-RET-01', 'qty' => 1]]],
                    ['customer' => 'Butet Kartaredjasa', 'table' => '05', 'type' => 'DINE_IN', 'pay' => 'CASH', 'ref' => null, 'items' => [['sku' => 'PRD-ESP-01', 'qty' => 2]]],
                    ['customer' => 'Maudy Ayunda', 'table' => '02', 'type' => 'DINE_IN', 'pay' => 'QRIS_MIDTRANS', 'ref' => 'qris-mpm-mlo-005', 'items' => [['sku' => 'PRD-ESP-03', 'qty' => 2]]],
                    ['customer' => 'Eross Candra', 'table' => '-', 'type' => 'TAKE_AWAY', 'pay' => 'CASH', 'ref' => null, 'items' => [['sku' => 'PRD-ESP-02', 'qty' => 2]]],
                    ['customer' => 'Kunto Aji', 'table' => '04', 'type' => 'DINE_IN', 'pay' => 'QRIS_MIDTRANS', 'ref' => 'qris-mpm-mlo-007', 'items' => [['sku' => 'PRD-ESP-04', 'qty' => 2]]],
                ],
                'calib' => ['customer' => 'Barista Malioboro (Dial-in)', 'items' => [['sku' => 'PRD-ESP-01', 'qty' => 2]]],
            ],
        ];

        $globalOrderCounter = 1;

        foreach ($branchesOrdersConfig as $bCfg) {
            $branch = $bCfg['branch'];
            $shift = $bCfg['shift'];
            $cashier = $bCfg['cashier'];
            $warehouse = $bCfg['warehouse'];

            foreach ($bCfg['orders'] as $idx => $ord) {
                $orderNum = sprintf('ORD-%s-%04d', date('Ymd'), $globalOrderCounter++);
                $subtotal = 0;
                $itemsToCreate = [];

                foreach ($ord['items'] as $item) {
                    $p = $prodModels[$item['sku']];
                    $lineSub = $p->selling_price * $item['qty'];
                    $subtotal += $lineSub;
                    $itemsToCreate[] = [
                        'product_id' => $p->id,
                        'product' => $p,
                        'quantity' => $item['qty'],
                        'unit_price' => $p->selling_price,
                        'subtotal' => $lineSub,
                    ];
                }

                $pb1Tax = round($subtotal * 0.11);
                $totalAmount = $subtotal + $pb1Tax;

                $orderTimestamp = Carbon::now()->subMinutes(180 - ($idx * 18));

                $posOrder = PosOrder::updateOrCreate(
                    ['order_number' => $orderNum],
                    [
                        'shift_id' => $shift->id,
                        'cashier_id' => $cashier->id,
                        'branch_id' => $branch->id,
                        'order_type' => $ord['type'],
                        'customer_name' => $ord['customer'],
                        'table_number' => $ord['table'],
                        'notes' => 'Pesanan reguler kasir terverifikasi',
                        'subtotal' => $subtotal,
                        'discount_amount' => 0.00,
                        'tax_amount' => $pb1Tax,
                        'total_amount' => $totalAmount,
                        'payment_method' => $ord['pay'],
                        'payment_status' => 'PAID',
                        'payment_reference' => $ord['ref'],
                        'created_at' => $orderTimestamp,
                    ]
                );

                foreach ($itemsToCreate as $itemData) {
                    $orderItem = PosOrderItem::updateOrCreate(
                        ['pos_order_id' => $posOrder->id, 'product_id' => $itemData['product_id']],
                        [
                            'quantity' => $itemData['quantity'],
                            'unit_price' => $itemData['unit_price'],
                            'subtotal' => $itemData['subtotal'],
                        ]
                    );

                    $prod = $itemData['product'];
                    $recipes = ProductRecipe::with('ingredientProduct')->where('product_id', $prod->id)->get();

                    if ($recipes->isNotEmpty()) {
                        foreach ($recipes as $rec) {
                            $deductQty = (float) $rec->quantity_required * $itemData['quantity'];
                            RecipeDeduction::create([
                                'pos_order_item_id' => $orderItem->id,
                                'ingredient_product_id' => $rec->ingredient_product_id,
                                'warehouse_id' => $warehouse->id,
                                'quantity_deducted' => $deductQty,
                                'unit' => $rec->unit,
                                'deduction_timestamp' => $orderTimestamp,
                            ]);

                            StockCard::create([
                                'warehouse_id' => $warehouse->id,
                                'product_id' => $rec->ingredient_product_id,
                                'lot_id' => null,
                                'reference_type' => 'POS_SALE',
                                'reference_id' => $posOrder->id,
                                'quantity_in' => 0.00,
                                'quantity_out' => $deductQty,
                                'balance_after' => 200.00,
                                'cost_per_unit' => (float) ($rec->ingredientProduct?->cost_price ?? 0),
                                'notes' => "Auto-deduct POS Resep [{$prod->name}] Order [{$posOrder->order_number}]",
                                'created_at' => $orderTimestamp,
                            ]);
                        }
                    } else {
                        StockCard::create([
                            'warehouse_id' => $warehouse->id,
                            'product_id' => $prod->id,
                            'lot_id' => null,
                            'reference_type' => 'POS_SALE',
                            'reference_id' => $posOrder->id,
                            'quantity_in' => 0.00,
                            'quantity_out' => (float) $itemData['quantity'],
                            'balance_after' => 15.00,
                            'cost_per_unit' => (float) $prod->cost_price,
                            'notes' => "POS Retail Sale [{$posOrder->order_number}]",
                            'created_at' => $orderTimestamp,
                        ]);
                    }
                }
            }

            // Non-Revenue Order (Calibration)
            $calibOrderNum = 'ORD-CALIB-'.$bCfg['prefix'].'-'.date('Ymd');
            $calibOrder = PosOrder::updateOrCreate(
                ['order_number' => $calibOrderNum],
                [
                    'shift_id' => $shift->id,
                    'cashier_id' => $cashier->id,
                    'branch_id' => $branch->id,
                    'order_type' => 'NON_REVENUE',
                    'customer_name' => $bCfg['calib']['customer'],
                    'table_number' => 'BAR',
                    'notes' => 'Dialing-in espresso grinder pagi hari',
                    'non_revenue_reason' => 'CALIBRATION',
                    'subtotal' => 0.00,
                    'discount_amount' => 0.00,
                    'tax_amount' => 0.00,
                    'total_amount' => 0.00,
                    'payment_method' => 'NON_REVENUE',
                    'payment_status' => 'PAID',
                    'created_at' => Carbon::now()->setTime(7, 45),
                ]
            );

            PosOrderItem::updateOrCreate(
                ['pos_order_id' => $calibOrder->id, 'product_id' => $prodModels['PRD-ESP-01']->id],
                ['quantity' => 2, 'unit_price' => 0.00, 'subtotal' => 0.00]
            );

            StockCard::create([
                'warehouse_id' => $warehouse->id,
                'product_id' => $prodModels['RAW-RB-HOUSE']->id,
                'lot_id' => null,
                'reference_type' => 'ADJUSTMENT',
                'reference_id' => $calibOrder->id,
                'quantity_in' => 0.00,
                'quantity_out' => 36.00, // 2 shot @ 18g
                'balance_after' => 190.00,
                'cost_per_unit' => (float) $prodModels['RAW-RB-HOUSE']->cost_price,
                'notes' => "Dialing-in Grinder Barista [{$bCfg['prefix']}] Order [{$calibOrder->order_number}]",
                'created_at' => Carbon::now()->setTime(7, 45),
            ]);
        }

        // 16. AUDIT TRAIL LOG SAMPLE
        AuditLog::create([
            'user_id' => $userModels['admin@kafeina.id']->id,
            'event_type' => 'INSERT',
            'auditable_type' => 'App\Models\Branch',
            'auditable_id' => $branchModels['HQ-CENTRAL']->id,
            'old_values' => null,
            'new_values' => json_encode(['system' => 'KAFEINA ERP', 'status' => 'PRODUCTION_READY', 'version' => '1.0.0'], JSON_UNESCAPED_UNICODE),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) KAFEINA-ERP-SUITE',
            'created_at' => Carbon::now()->subDays(1),
        ]);

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
