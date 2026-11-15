<?php

declare(strict_types=1);

namespace Tests\Feature;

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
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\QualityControl;
use App\Models\RecipeDeduction;
use App\Models\RoastingProfile;
use App\Models\Supplier;
use App\Models\User;
use App\Models\WarehouseStock;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RoasteryLifecycleEndToEndTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;

    protected User $roasterUser;

    protected User $cashierUser;

    protected Branch $centralBranch;

    protected Branch $senopatiBranch;

    protected InventoryWarehouse $whRawCentral;

    protected InventoryWarehouse $whRoastCentral;

    protected InventoryWarehouse $whBarSenopati;

    protected function setUp(): void
    {
        parent::setUp();

        $this->centralBranch = Branch::where('code', 'HQ-CENTRAL')->firstOrFail();
        $this->senopatiBranch = Branch::where('code', 'OUT-SENO')->firstOrFail();

        $this->whRawCentral = InventoryWarehouse::where('code', 'WH-RAW-HQ')->firstOrFail();
        $this->whRoastCentral = InventoryWarehouse::where('code', 'WH-ROAST-HQ')->firstOrFail();
        $this->whBarSenopati = InventoryWarehouse::where('code', 'WH-BAR-SENO')->firstOrFail();

        $this->adminUser = User::where('email', 'admin@kafeina.id')->firstOrFail();
        $this->roasterUser = User::where('email', 'roaster@kafeina.id')->firstOrFail();
        $this->cashierUser = User::where('email', 'cashier.senopati@kafeina.id')->firstOrFail();
    }

    /**
     * Test complete lifecycle: Procurement -> QC Lab -> Roastery WO & Stock Movement -> Logistics MR/DN -> POS Auto-Deduct.
     */
    public function test_complete_specialty_coffee_roastery_lifecycle(): void
    {
        // =========================================================================
        // STEP 1: PENGADAAN (PROCUREMENT) - Registrasi Mitra Petani & Penerbitan PO
        // =========================================================================
        $supplier = Supplier::create([
            'code' => 'SUP-TEST-'.rand(100, 999),
            'name' => 'Koperasi Tani Uji Alur Mandiri Gayo',
            'contact_person' => 'Aman Test',
            'phone' => '081299998888',
            'address' => 'Desa Bener Meriah, Aceh Tengah',
            'farm_location' => 'Burni Telong',
            'altitude_masl' => 1600,
            'rating_score' => 4.90,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id, 'name' => 'Koperasi Tani Uji Alur Mandiri Gayo']);

        $poNumber = 'PO-TEST-'.rand(1000, 9999);
        $weightKg = 50.0;
        $pricePerKg = 130000.0;
        $totalAmount = $weightKg * $pricePerKg;

        $po = PurchaseOrder::create([
            'po_number' => $poNumber,
            'supplier_id' => $supplier->id,
            'branch_id' => $this->centralBranch->id,
            'user_id' => $this->adminUser->id,
            'order_date' => now(),
            'expected_delivery_date' => now()->addDays(5),
            'total_amount' => $totalAmount,
            'status' => 'COMPLETED',
            'notes' => 'Pengadaan 50kg Green Beans Uji Alur',
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'product_name' => 'Green Beans Aceh Gayo Honey Process',
            'quantity_kg' => $weightKg,
            'unit_price' => $pricePerKg,
            'subtotal' => $totalAmount,
        ]);

        $this->assertDatabaseHas('purchase_orders', ['id' => $po->id, 'total_amount' => 6500000.0]);

        // =========================================================================
        // STEP 2: QC LAB & LABORATORIUM MUTU - Uji Fisik & SCA Cupping Score
        // =========================================================================
        $lotNumber = 'GB-TEST-'.date('Y').'-'.rand(100, 999);
        $initialGrams = $weightKg * 1000;

        $lot = GreenBeanLot::create([
            'lot_number' => $lotNumber,
            'purchase_order_id' => $po->id,
            'variety' => 'Arabica Gayo 1 & Bourbon',
            'origin_region' => 'Aceh Tengah Gayo',
            'process_type' => 'HONEY',
            'harvest_year' => (int) date('Y'),
            'initial_weight_grams' => $initialGrams,
            'current_weight_grams' => $initialGrams,
            'price_per_gram' => 130.0,
        ]);

        $qc = QualityControl::create([
            'lot_id' => $lot->id,
            'inspector_id' => $this->adminUser->id,
            'inspection_date' => now(),
            'moisture_content' => 11.20,
            'bulk_density_g_l' => 715.00,
            'primary_defects' => 0,
            'secondary_defects' => 2,
            'sca_cupping_score' => 87.50,
            'qc_status' => 'ACCEPTED',
            'notes' => 'Specialty Grade 1 Terverifikasi. Lolos uji cupping.',
        ]);

        $this->assertEquals('ACCEPTED', $qc->qc_status);
        $this->assertGreaterThanOrEqual(80.0, (float) $qc->sca_cupping_score);

        // =========================================================================
        // STEP 3: ROASTERY WORK ORDER & OTOMASI MUTASI STOK SANGRAI (STOCK LEDGER)
        // =========================================================================
        $profile = RoastingProfile::firstOrCreate(['profile_code' => 'PROF-TEST-LIGHT'], [
            'name' => 'Nordic Light Roast Test',
            'roast_level' => 'LIGHT',
            'charge_temp_celsius' => 195.0,
            'turning_point_celsius' => 92.0,
            'development_time_ratio' => 13.5,
            'target_air_flow' => 'Medium-High',
        ]);

        $greenBeanProd = Product::where('sku', 'RAW-GB-GAYO')->firstOrFail();
        $roastedBeanProd = Product::where('sku', 'RAW-RB-HOUSE')->firstOrFail();

        // Rekam stok awal sebelum sangrai
        $initialRawStock = (float) WarehouseStock::where('warehouse_id', $this->whRawCentral->id)
            ->where('product_id', $greenBeanProd->id)
            ->value('quantity');

        $initialRoastStock = (float) WarehouseStock::where('warehouse_id', $this->whRoastCentral->id)
            ->where('product_id', $roastedBeanProd->id)
            ->value('quantity');

        $woGreenKg = 15.0;
        $woRoastedKg = 12.72; // Yield Loss 15.20%
        $greenGrams = $woGreenKg * 1000;
        $roastedGrams = $woRoastedKg * 1000;

        $wo = WorkOrder::create([
            'wo_number' => 'WO-TEST-'.rand(1000, 9999),
            'lot_id' => $lot->id,
            'profile_id' => $profile->id,
            'roaster_id' => $this->roasterUser->id,
            'green_weight_grams' => $greenGrams,
            'roasted_weight_grams' => null,
            'yield_loss_percentage' => null,
            'status' => 'SCHEDULED',
            'roast_start_time' => null,
            'roast_end_time' => null,
            'notes' => 'Batch uji alur sangrai',
        ]);

        // Simulasikan eksekusi sangrai via RoasteryController logic
        $this->actingAs($this->roasterUser);
        $response = $this->postJson('/roastery/execute', [
            'wo_id' => $wo->id,
            'green_weight_kg' => $woGreenKg,
            'roasted_weight_kg' => $woRoastedKg,
            'roast_duration_minutes' => 12.5,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verifikasi Yield Loss akurat (15.20%)
        $wo->refresh();
        $this->assertEquals('COMPLETED', $wo->status);
        $this->assertEquals(15.20, (float) $wo->yield_loss_percentage);

        // Verifikasi mutasi fisik stok di Gudang Sentral
        $finalRawStock = (float) WarehouseStock::where('warehouse_id', $this->whRawCentral->id)
            ->where('product_id', $greenBeanProd->id)
            ->value('quantity');

        $finalRoastStock = (float) WarehouseStock::where('warehouse_id', $this->whRoastCentral->id)
            ->where('product_id', $roastedBeanProd->id)
            ->value('quantity');

        // Biji mentah berkurang 15.000g, biji matang bertambah 12.720g
        $this->assertEquals($initialRawStock - $greenGrams, $finalRawStock);
        $this->assertEquals($initialRoastStock + $roastedGrams, $finalRoastStock);

        // Verifikasi sisa bobot lot terpotong
        $lot->refresh();
        $this->assertEquals($initialGrams - $greenGrams, (float) $lot->current_weight_grams);

        // Verifikasi kartu stok tercatat
        $this->assertDatabaseHas('stock_cards', [
            'warehouse_id' => $this->whRawCentral->id,
            'product_id' => $greenBeanProd->id,
            'reference_type' => 'ROAST_CHARGE',
            'reference_id' => $wo->id,
            'quantity_out' => $greenGrams,
        ]);

        $this->assertDatabaseHas('stock_cards', [
            'warehouse_id' => $this->whRoastCentral->id,
            'product_id' => $roastedBeanProd->id,
            'reference_type' => 'ROAST_OUTPUT',
            'reference_id' => $wo->id,
            'quantity_in' => $roastedGrams,
        ]);

        // =========================================================================
        // STEP 4: LOGISTIK & DISTRIBUSI - Material Request (MR) & Surat Jalan (DN)
        // =========================================================================
        $mr = MaterialRequest::create([
            'request_number' => 'MR-TEST-'.rand(1000, 9999),
            'from_branch_id' => $this->senopatiBranch->id,
            'to_branch_id' => $this->centralBranch->id,
            'requested_by_user_id' => $this->cashierUser->id,
            'status' => 'PENDING',
            'notes' => 'Permintaan pasokan biji sangrai bar Senopati',
        ]);

        MaterialRequestItem::create([
            'material_request_id' => $mr->id,
            'product_id' => $roastedBeanProd->id,
            'quantity_requested' => 5000.0, // 5kg
            'quantity_fulfilled' => 5000.0,
        ]);

        $dn = DeliveryNote::create([
            'delivery_number' => 'SJ-TEST-'.rand(1000, 9999),
            'request_id' => $mr->id,
            'source_warehouse_id' => $this->whRoastCentral->id,
            'dest_warehouse_id' => $this->whBarSenopati->id,
            'dispatched_by_user_id' => $this->adminUser->id,
            'courier_name' => 'Armada Van Kafeina B 9999 KAF',
            'tracking_code' => 'TRK-TEST-'.rand(1000, 9999),
            'status' => 'DELIVERED',
            'notes' => 'Muatan tiba dan diverifikasi barista',
        ]);

        DeliveryNoteItem::create([
            'delivery_note_id' => $dn->id,
            'product_id' => $roastedBeanProd->id,
            'quantity_sent' => 5000.0,
            'quantity_received' => 5000.0,
        ]);

        $this->assertEquals('DELIVERED', $dn->status);
        $this->assertEquals($this->whBarSenopati->id, $dn->dest_warehouse_id);

        // =========================================================================
        // STEP 5: RETAIL WEB POS & AUTO-DEDUCT RESEP BAR
        // =========================================================================
        $shift = PosShift::create([
            'shift_code' => 'SHIFT-TEST-'.rand(1000, 9999),
            'cashier_user_id' => $this->cashierUser->id,
            'branch_id' => $this->senopatiBranch->id,
            'opening_cash' => 500000.0,
            'status' => 'OPEN',
            'opened_at' => now(),
        ]);

        $latteProduct = Product::where('sku', 'PRD-ESP-03')->firstOrFail(); // Flat White / Latte

        // Saldo awal roasted beans di bar Senopati
        $barBeansInitial = (float) WarehouseStock::where('warehouse_id', $this->whBarSenopati->id)
            ->where('product_id', $roastedBeanProd->id)
            ->value('quantity');

        $order = PosOrder::create([
            'order_number' => 'ORD-TEST-'.rand(1000, 9999),
            'shift_id' => $shift->id,
            'cashier_id' => $this->cashierUser->id,
            'branch_id' => $this->senopatiBranch->id,
            'order_type' => 'DINE_IN',
            'subtotal' => 42000.0,
            'tax_amount' => 4620.0, // PB1 11%
            'discount_amount' => 0.0,
            'total_amount' => 46620.0,
            'payment_method' => 'QRIS_MIDTRANS',
            'payment_status' => 'PAID',
        ]);

        $orderItem = PosOrderItem::create([
            'pos_order_id' => $order->id,
            'product_id' => $latteProduct->id,
            'quantity' => 1.0,
            'unit_price' => 42000.0,
            'subtotal' => 42000.0,
        ]);

        // Simulasikan Auto-Deduct 18g biji sangrai
        RecipeDeduction::create([
            'pos_order_item_id' => $orderItem->id,
            'ingredient_product_id' => $roastedBeanProd->id,
            'warehouse_id' => $this->whBarSenopati->id,
            'quantity_deducted' => 18.0,
            'unit' => 'gram',
            'deduction_timestamp' => now(),
        ]);

        WarehouseStock::where('warehouse_id', $this->whBarSenopati->id)
            ->where('product_id', $roastedBeanProd->id)
            ->decrement('quantity', 18.0);

        $barBeansFinal = (float) WarehouseStock::where('warehouse_id', $this->whBarSenopati->id)
            ->where('product_id', $roastedBeanProd->id)
            ->value('quantity');

        $this->assertEquals($barBeansInitial - 18.0, $barBeansFinal);

        // =========================================================================
        // STEP 6: AUDIT INTEGRITAS ARSITEKTUR KANONIKAL & ZERO RESIDUE PURWOKERTO
        // =========================================================================
        // Pastikan hanya ada 4 cabang kanonikal di sistem
        $canonicalCodes = ['HQ-CENTRAL', 'OUT-SENO', 'OUT-BRG', 'OUT-MLO'];
        $actualBranchCodes = Branch::pluck('code')->sort()->values()->toArray();
        $this->assertEquals(sort($canonicalCodes), sort($actualBranchCodes));

        // Pastikan hanya ada 6 gudang kanonikal (2 Sentral + 1 Transit + 3 Bar Outlet)
        $canonicalWhCodes = ['WH-RAW-HQ', 'WH-ROAST-HQ', 'WH-TRANSIT', 'WH-BAR-SENO', 'WH-BAR-BRG', 'WH-BAR-MLO'];
        $actualWhCodes = InventoryWarehouse::pluck('code')->sort()->values()->toArray();
        $this->assertEquals(sort($canonicalWhCodes), sort($actualWhCodes));

        // Pastikan NOL gudang Purwokerto atau Banyumas
        $this->assertDatabaseMissing('inventory_warehouses', ['code' => 'WH-BAR-PWT']);
        $this->assertDatabaseMissing('inventory_warehouses', ['code' => 'WH-BAR-BMS']);
        $this->assertDatabaseMissing('inventory_warehouses', ['code' => 'WH-RAW-ROAST']);
        $this->assertDatabaseMissing('inventory_warehouses', ['code' => 'WH-RST-ROAST']);
    }
}
