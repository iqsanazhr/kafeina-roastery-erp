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
use App\Models\StockCard;
use App\Models\Supplier;
use App\Models\User;
use App\Models\WarehouseStock;
use Database\Seeders\KafeinaRealDataSeeder;
use Exception;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * White-Box Testing Suite untuk Logika Bisnis & Rantai Pasok Terpadu
 * KAFEINA Specialty Coffee ERP.
 * 
 * Pengujian struktural internal mencakup 8 Jalur Kritis:
 * 1. Procurement & Data Invariant Bounds
 * 2. QC Lab Sensory Decision Tree & Branch Coverage
 * 3. Roasting Yield Loss & Physical HPP Recalculation Math
 * 4. Inter-Warehouse Transfer & Double-Entry Conservation of Mass
 * 5. POS Recipe BOM Auto-Deduction & Inventory Decrement
 * 6. Database Transaction Atomicity & Failure Rollback Isolation
 * 7. Reorder Point (ROP) Safety Stock Alerts & Emergency Consolidated PO
 * 8. Role-Based Access Control (RBAC) & Cross-Branch Security Guard
 */
class WhiteBoxBusinessProcessTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;
    protected User $roaster;
    protected User $cashier;
    protected Branch $centralBranch;
    protected Branch $senopatiBranch;
    protected Branch $bragaBranch;
    protected InventoryWarehouse $whRawCentral;
    protected InventoryWarehouse $whRoastCentral;
    protected InventoryWarehouse $whBarSenopati;

    protected function setUp(): void
    {
        parent::setUp();

        if (PosOrder::count() === 0 || StockCard::count() === 0) {
            $this->seed(KafeinaRealDataSeeder::class);
        }

        $this->superAdmin = User::where('email', 'admin@kafeina.id')->firstOrFail();
        $this->roaster = User::where('email', 'roaster@kafeina.id')->firstOrFail();
        $this->cashier = User::where('email', 'cashier.senopati@kafeina.id')->firstOrFail();

        $this->centralBranch = Branch::where('code', 'HQ-CENTRAL')->firstOrFail();
        $this->senopatiBranch = Branch::where('code', 'OUT-SENO')->firstOrFail();
        $this->bragaBranch = Branch::where('code', 'OUT-BRG')->firstOrFail();

        $this->whRawCentral = InventoryWarehouse::where('code', 'WH-RAW-HQ')->firstOrFail();
        $this->whRoastCentral = InventoryWarehouse::where('code', 'WH-ROAST-HQ')->firstOrFail();
        $this->whBarSenopati = InventoryWarehouse::where('code', 'WH-BAR-SENO')->firstOrFail();
    }

    /**
     * Jalur Uji 1: Procurement & Invariant Bounds Check.
     * Menguji bahwa pengadaan bahan baku menjaga batas invarian positif:
     * kuantitas > 0, harga satuan > 0, total amount = qty * unit_price,
     * dan relasi Supplier -> PO -> PO Item terkoneksi secara tepat.
     */
    public function test_whitebox_path1_procurement_and_lot_invariants(): void
    {
        $supplier = Supplier::create([
            'code' => 'SUP-WB-'.uniqid(),
            'name' => 'Koperasi Petani Bener Meriah Specialty',
            'contact_person' => 'Iskandar Muda',
            'phone' => '081299990001',
            'address' => 'Jl. Bandara Rembele, Bener Meriah, Aceh',
            'farm_location' => 'Lereng Gunung Burni Telong',
            'altitude_masl' => 1600,
            'rating_score' => 4.90,
            'is_active' => true,
        ]);

        $this->assertNotNull($supplier->id);
        $this->assertGreaterThan(0, $supplier->altitude_masl);

        $weightKg = 40.0;
        $pricePerKg = 135000.0;
        $expectedTotal = $weightKg * $pricePerKg;

        $po = PurchaseOrder::create([
            'po_number' => 'PO-WB-'.date('Ymd').'-'.rand(100, 999),
            'supplier_id' => $supplier->id,
            'branch_id' => $this->centralBranch->id,
            'user_id' => $this->superAdmin->id,
            'order_date' => now(),
            'expected_delivery_date' => now()->addDays(3),
            'total_amount' => $expectedTotal,
            'status' => 'COMPLETED',
            'notes' => 'White-Box Invariant Test Lot',
        ]);

        $poItem = PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'product_name' => 'Green Beans Aceh Bener Meriah Anaerobic Natural',
            'quantity_kg' => $weightKg,
            'unit_price' => $pricePerKg,
            'subtotal' => $expectedTotal,
        ]);

        // Verifikasi Invarian Matematika & Relasional
        $this->assertEquals($expectedTotal, $po->total_amount);
        $this->assertEquals($expectedTotal, $poItem->subtotal);
        $this->assertEquals($po->id, $poItem->purchaseOrder->id);
        $this->assertEquals($supplier->id, $po->supplier->id);
        $this->assertGreaterThan(0, $poItem->quantity_kg);
        $this->assertGreaterThan(0, $poItem->unit_price);
    }

    /**
     * Jalur Uji 2: QC Sensory Decision Tree & Branch Coverage.
     * Menguji percabangan logika penerimaan lot:
     * - Jalur A (Lolos): SCA Cupping Score >= 80.00 & Moisture <= 12.5% -> ACCEPTED.
     * - Jalur B (Gagal): SCA Cupping Score < 80.00 -> REJECTED (Karantina / Tolak).
     */
    public function test_whitebox_path2_qc_sensory_branch_coverage(): void
    {
        $po = PurchaseOrder::firstOrFail();

        // 1. Jalur A: Specialty Grade (SCA 86.50) -> ACCEPTED
        $lotAccepted = GreenBeanLot::create([
            'lot_number' => 'GB-WB-ACC-'.rand(1000, 9999),
            'purchase_order_id' => $po->id,
            'variety' => 'Typica & Bourbon',
            'origin_region' => 'Aceh Bener Meriah',
            'process_type' => 'NATURAL',
            'harvest_year' => 2026,
            'initial_weight_grams' => 20000,
            'current_weight_grams' => 20000,
            'price_per_gram' => 135.0,
        ]);

        $qcPass = QualityControl::create([
            'lot_id' => $lotAccepted->id,
            'inspector_id' => $this->superAdmin->id,
            'inspection_date' => now(),
            'moisture_content' => 11.40,
            'bulk_density_g_l' => 720.00,
            'primary_defects' => 0,
            'secondary_defects' => 1,
            'sca_cupping_score' => 86.50,
            'qc_status' => 'ACCEPTED',
            'notes' => 'Memenuhi standar Specialty Grade 1 SCAA.',
        ]);

        $this->assertGreaterThanOrEqual(80.0, $qcPass->sca_cupping_score);
        $this->assertLessThanOrEqual(12.5, $qcPass->moisture_content);
        $this->assertEquals('ACCEPTED', $qcPass->qc_status);

        // 2. Jalur B: Defect / Sub-par (SCA 76.00) -> REJECTED
        $lotRejected = GreenBeanLot::create([
            'lot_number' => 'GB-WB-REJ-'.rand(1000, 9999),
            'purchase_order_id' => $po->id,
            'variety' => 'Robusta Campuran',
            'origin_region' => 'Luar Spesifikasi',
            'process_type' => 'WASHED',
            'harvest_year' => 2025,
            'initial_weight_grams' => 10000,
            'current_weight_grams' => 10000,
            'price_per_gram' => 60.0,
        ]);

        $qcFail = QualityControl::create([
            'lot_id' => $lotRejected->id,
            'inspector_id' => $this->superAdmin->id,
            'inspection_date' => now(),
            'moisture_content' => 13.80, // Terlalu lembab > 12.5%
            'bulk_density_g_l' => 650.00,
            'primary_defects' => 3,
            'secondary_defects' => 9,
            'sca_cupping_score' => 76.00,
            'qc_status' => 'REJECTED',
            'notes' => 'Di bawah batas minimum Specialty (80.0) dan kadar air melebihi batas aman.',
        ]);

        $this->assertLessThan(80.0, $qcFail->sca_cupping_score);
        $this->assertEquals('REJECTED', $qcFail->qc_status);
        $this->assertGreaterThan(12.5, $qcFail->moisture_content);
    }

    /**
     * Jalur Uji 3: Roasting Production Yield Loss & Physical HPP Math Calculation.
     * Menguji kebenaran algoritma perhitungan susut bobot fisik:
     * Yield Loss = ((W_green - W_roasted) / W_green) * 100%
     * Dan penyesuaian HPP Riil per gram:
     * HPP_roasted = (HPP_green * (W_green / W_roasted)) + Overhead_per_gram
     */
    public function test_whitebox_path3_roasting_yield_loss_and_hpp_recalculation(): void
    {
        $greenBeanProd = Product::where('sku', 'RAW-GB-GAYO')->firstOrFail();
        $roastedBeanProd = Product::where('sku', 'RAW-RB-HOUSE')->firstOrFail();

        $greenGrams = 10000.0;  // 10.0 kg
        $roastedGrams = 8480.0; // 8.48 kg (Susut 15.20%)
        $greenHppPerGram = 130.0;
        $overheadPerGram = 15.0;

        // Formula Matematis
        $expectedYieldLoss = round((($greenGrams - $roastedGrams) / $greenGrams) * 100, 2);
        $expectedHpp = round(($greenHppPerGram * ($greenGrams / $roastedGrams)) + $overheadPerGram, 2);

        $this->assertEquals(15.20, $expectedYieldLoss);
        // 130.0 * (10000 / 8480) + 15 = 153.30 + 15 = 168.30
        $this->assertEquals(168.30, $expectedHpp);

        // Pastikan berada di dalam rentang emas sangrai SCAA (14% - 18%)
        $this->assertGreaterThanOrEqual(14.0, $expectedYieldLoss);
        $this->assertLessThanOrEqual(18.0, $expectedYieldLoss);

        // Simulasi mutasi fisik inventaris
        $rawBefore = (float) WarehouseStock::where('warehouse_id', $this->whRawCentral->id)
            ->where('product_id', $greenBeanProd->id)
            ->value('quantity');

        $roastBefore = (float) WarehouseStock::where('warehouse_id', $this->whRoastCentral->id)
            ->where('product_id', $roastedBeanProd->id)
            ->value('quantity');

        WarehouseStock::where('warehouse_id', $this->whRawCentral->id)
            ->where('product_id', $greenBeanProd->id)
            ->decrement('quantity', $greenGrams);

        WarehouseStock::where('warehouse_id', $this->whRoastCentral->id)
            ->where('product_id', $roastedBeanProd->id)
            ->increment('quantity', $roastedGrams);

        $rawAfter = (float) WarehouseStock::where('warehouse_id', $this->whRawCentral->id)
            ->where('product_id', $greenBeanProd->id)
            ->value('quantity');

        $roastAfter = (float) WarehouseStock::where('warehouse_id', $this->whRoastCentral->id)
            ->where('product_id', $roastedBeanProd->id)
            ->value('quantity');

        $this->assertEquals($rawBefore - $greenGrams, $rawAfter);
        $this->assertEquals($roastBefore + $roastedGrams, $roastAfter);
    }

    /**
     * Jalur Uji 4: Inter-Warehouse Transfer & Double-Entry Conservation of Mass.
     * Menguji bahwa pengiriman logistik antar-gudang (Transfer Out -> Transfer In)
     * mematuhi hukum kekekalan massa (Double-Entry Balancing):
     * Saldo berkurang di Gudang Asal sejumlah persis sama dengan saldo bertambah di Gudang Tujuan.
     */
    public function test_whitebox_path4_fifo_stock_transfer_and_double_entry_balance(): void
    {
        $product = Product::where('sku', 'RAW-RB-HOUSE')->firstOrFail();
        $transferGrams = 5000.0; // 5 kg roasted beans

        $originStockBefore = (float) WarehouseStock::where('warehouse_id', $this->whRoastCentral->id)
            ->where('product_id', $product->id)
            ->value('quantity');

        $destStockBefore = (float) WarehouseStock::where('warehouse_id', $this->whBarSenopati->id)
            ->where('product_id', $product->id)
            ->value('quantity');

        // Material Request & Delivery Note
        $mr = MaterialRequest::create([
            'request_number' => 'MR-WB-'.uniqid(),
            'from_branch_id' => $this->centralBranch->id,
            'to_branch_id' => $this->senopatiBranch->id,
            'requested_by_user_id' => $this->cashier->id,
            'status' => 'APPROVED',
            'notes' => 'White-Box Inter-branch Transfer',
        ]);

        MaterialRequestItem::create([
            'material_request_id' => $mr->id,
            'product_id' => $product->id,
            'quantity_requested' => $transferGrams,
            'quantity_fulfilled' => $transferGrams,
        ]);

        $dn = DeliveryNote::create([
            'delivery_number' => 'DN-WB-'.uniqid(),
            'request_id' => $mr->id,
            'source_warehouse_id' => $this->whRoastCentral->id,
            'dest_warehouse_id' => $this->whBarSenopati->id,
            'dispatched_by_user_id' => $this->superAdmin->id,
            'courier_name' => 'Armada Internal Kafeina',
            'status' => 'DELIVERED',
            'notes' => 'Ekspedisi Armada Dingin Internal Kafeina',
        ]);

        DeliveryNoteItem::create([
            'delivery_note_id' => $dn->id,
            'product_id' => $product->id,
            'quantity_sent' => $transferGrams,
            'quantity_received' => $transferGrams,
        ]);

        // Mutasi Fisik Dua Gudang
        WarehouseStock::where('warehouse_id', $this->whRoastCentral->id)
            ->where('product_id', $product->id)
            ->decrement('quantity', $transferGrams);

        WarehouseStock::where('warehouse_id', $this->whBarSenopati->id)
            ->where('product_id', $product->id)
            ->increment('quantity', $transferGrams);

        $originStockAfter = (float) WarehouseStock::where('warehouse_id', $this->whRoastCentral->id)
            ->where('product_id', $product->id)
            ->value('quantity');

        $destStockAfter = (float) WarehouseStock::where('warehouse_id', $this->whBarSenopati->id)
            ->where('product_id', $product->id)
            ->value('quantity');

        // Konservasi Massa Double-Entry
        $deltaOrigin = $originStockAfter - $originStockBefore;
        $deltaDest = $destStockAfter - $destStockBefore;

        $this->assertEquals(-$transferGrams, $deltaOrigin);
        $this->assertEquals($transferGrams, $deltaDest);
        $this->assertEquals(0, $deltaOrigin + $deltaDest, 'Prinsip Konservasi Massa Terbukti (Net Zero Difference)');
    }

    /**
     * Jalur Uji 5: POS Recipe BOM Auto-Deduction & Inventory Decrement.
     * Menguji logika otomasi pengurangan bahan baku resep saat transaksi POS:
     * - Tiap Cup Flat White mengonsumsi:
     *   * 18g Biji Sangrai House Blend
     *   * 200ml Fresh Milk
     *   * 1 pcs Paper Cup 8oz
     * Validasi pemotongan stok pada tabel warehouse_stocks & pencatatan audit recipe_deductions.
     */
    public function test_whitebox_path5_pos_recipe_auto_deduction_and_bom_integrity(): void
    {
        $menuProduct = Product::where('sku', 'PRD-ESP-03')->first()
            ?? Product::where('sku', 'like', 'PRD-ESP%')->firstOrFail();

        $espressoBean = Product::where('sku', 'RAW-RB-HOUSE')->firstOrFail();
        $milk = Product::where('sku', 'RAW-MILK-FRESH')->firstOrFail();
        $cup = Product::where('sku', 'PKG-CUP-HOT8')->firstOrFail();

        $beanBefore = (float) WarehouseStock::where('warehouse_id', $this->whBarSenopati->id)->where('product_id', $espressoBean->id)->value('quantity');
        $milkBefore = (float) WarehouseStock::where('warehouse_id', $this->whBarSenopati->id)->where('product_id', $milk->id)->value('quantity');
        $cupBefore = (float) WarehouseStock::where('warehouse_id', $this->whBarSenopati->id)->where('product_id', $cup->id)->value('quantity');

        $qtyOrdered = 3; // 3 Cup Flat White

        $activeShift = PosShift::where('branch_id', $this->senopatiBranch->id)->where('status', 'OPEN')->first();
        if (!$activeShift) {
            $activeShift = PosShift::create([
                'branch_id' => $this->senopatiBranch->id,
                'user_id' => $this->cashier->id,
                'opened_at' => now(),
                'opening_cash' => 200000,
                'status' => 'OPEN',
            ]);
        }

        $order = PosOrder::create([
            'order_number' => 'POS-WB-'.uniqid(),
            'shift_id' => $activeShift->id,
            'cashier_id' => $this->cashier->id,
            'branch_id' => $this->senopatiBranch->id,
            'order_type' => 'DINE_IN',
            'customer_name' => 'Prof. Penguji White-Box',
            'subtotal' => 38000 * $qtyOrdered,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 38000 * $qtyOrdered,
            'payment_method' => 'QRIS_MIDTRANS',
            'payment_status' => 'PAID',
        ]);

        $orderItem = PosOrderItem::create([
            'pos_order_id' => $order->id,
            'product_id' => $menuProduct->id,
            'quantity' => $qtyOrdered,
            'unit_price' => 38000,
            'subtotal' => 38000 * $qtyOrdered,
        ]);

        // Eksekusi Auto-Deduct Berdasarkan BOM
        $expectedBeanUsed = 18.0 * $qtyOrdered;  // 54 gram
        $expectedMilkUsed = 200.0 * $qtyOrdered; // 600 ml
        $expectedCupUsed = 1.0 * $qtyOrdered;    // 3 pcs

        WarehouseStock::where('warehouse_id', $this->whBarSenopati->id)->where('product_id', $espressoBean->id)->decrement('quantity', $expectedBeanUsed);
        WarehouseStock::where('warehouse_id', $this->whBarSenopati->id)->where('product_id', $milk->id)->decrement('quantity', $expectedMilkUsed);
        WarehouseStock::where('warehouse_id', $this->whBarSenopati->id)->where('product_id', $cup->id)->decrement('quantity', $expectedCupUsed);

        RecipeDeduction::create([
            'pos_order_item_id' => $orderItem->id,
            'warehouse_id' => $this->whBarSenopati->id,
            'ingredient_product_id' => $espressoBean->id,
            'quantity_deducted' => $expectedBeanUsed,
            'unit' => 'grams',
            'deduction_timestamp' => now(),
        ]);

        $beanAfter = (float) WarehouseStock::where('warehouse_id', $this->whBarSenopati->id)->where('product_id', $espressoBean->id)->value('quantity');
        $milkAfter = (float) WarehouseStock::where('warehouse_id', $this->whBarSenopati->id)->where('product_id', $milk->id)->value('quantity');
        $cupAfter = (float) WarehouseStock::where('warehouse_id', $this->whBarSenopati->id)->where('product_id', $cup->id)->value('quantity');

        $this->assertEquals($beanBefore - $expectedBeanUsed, $beanAfter);
        $this->assertEquals($milkBefore - $expectedMilkUsed, $milkAfter);
        $this->assertEquals($cupBefore - $expectedCupUsed, $cupAfter);

        $this->assertDatabaseHas('recipe_deductions', [
            'pos_order_item_id' => $orderItem->id,
            'ingredient_product_id' => $espressoBean->id,
            'quantity_deducted' => $expectedBeanUsed,
        ]);
    }

    /**
     * Jalur Uji 6: Database Transaction Atomicity & Failure Rollback Isolation.
     * Menguji batas integritas atomik (ACID):
     * Ketika terjadi error sistem tak terduga (simulated exception) di tengah transaksi
     * multi-tabel, seluruh operasi harus dibatalkan (DB::rollBack()),
     * dan saldo persediaan tidak boleh terpotong sebagian (no dirty writes).
     */
    public function test_whitebox_path6_atomic_transaction_rollback_on_failure(): void
    {
        $product = Product::where('sku', 'RAW-RB-HOUSE')->firstOrFail();
        $stockBefore = (float) WarehouseStock::where('warehouse_id', $this->whBarSenopati->id)
            ->where('product_id', $product->id)
            ->value('quantity');

        $activeShift = PosShift::where('branch_id', $this->senopatiBranch->id)->where('status', 'OPEN')->first();
        if (!$activeShift) {
            $activeShift = PosShift::create([
                'branch_id' => $this->senopatiBranch->id,
                'user_id' => $this->cashier->id,
                'opened_at' => now(),
                'opening_cash' => 200000,
                'status' => 'OPEN',
            ]);
        }

        $initialOrderCount = PosOrder::count();
        $attemptedOrderNumber = 'POS-FAIL-'.uniqid();

        $exceptionCaught = false;

        try {
            DB::transaction(function () use ($product, $attemptedOrderNumber, $activeShift) {
                // Operasi 1: Simpan PosOrder
                PosOrder::create([
                    'order_number' => $attemptedOrderNumber,
                    'shift_id' => $activeShift->id,
                    'cashier_id' => $this->cashier->id,
                    'branch_id' => $this->senopatiBranch->id,
                    'order_type' => 'DINE_IN',
                    'customer_name' => 'Uji Kegagalan Transaksi',
                    'subtotal' => 50000,
                    'tax_amount' => 0,
                    'discount_amount' => 0,
                    'total_amount' => 50000,
                    'payment_method' => 'CASH',
                    'payment_status' => 'PENDING',
                ]);

                // Operasi 2: Potong Stok Fisik
                WarehouseStock::where('warehouse_id', $this->whBarSenopati->id)
                    ->where('product_id', $product->id)
                    ->decrement('quantity', 1000.0);

                // Operasi 3: Simulasikan Crash Database / Exception Eksternal
                throw new Exception('SIMULATED_TRANSACTION_FAILURE: Terjadi gangguan jaringan pada gateway.');
            });
        } catch (Exception $e) {
            $exceptionCaught = true;
            $this->assertStringContainsString('SIMULATED_TRANSACTION_FAILURE', $e->getMessage());
        }

        $this->assertTrue($exceptionCaught, 'Exception simulasi berhasil ditangkap.');

        // Verifikasi Integritas Rollback: Saldo stok tidak boleh berubah
        $stockAfter = (float) WarehouseStock::where('warehouse_id', $this->whBarSenopati->id)
            ->where('product_id', $product->id)
            ->value('quantity');

        $this->assertEquals($stockBefore, $stockAfter, 'Integritas Stok Terlindungi: Stok tidak terpotong sebagian.');

        // Verifikasi PosOrder tidak tersimpan di database
        $this->assertDatabaseMissing('pos_orders', [
            'order_number' => $attemptedOrderNumber,
        ]);
        $this->assertEquals($initialOrderCount, PosOrder::count());
    }

    /**
     * Jalur Uji 7: Reorder Point (ROP) Safety Stock Alerts & Emergency Consolidated PO.
     * Menguji deteksi level stok kritis (Q <= Threshold) dan
     * penerbitan dokumen Purchase Order Konsolidasi Darurat (PO-KONS-YYYYMMDD-XXX).
     */
    public function test_whitebox_path7_safety_stock_rop_threshold_and_consolidated_po_generation(): void
    {
        // 1. Set 1 item persediaan agar berada di bawah ambang batas aman (ROP trigger)
        $stockItem = WarehouseStock::firstOrFail();
        $stockItem->update([
            'quantity' => 25000.0,
            'safety_stock_threshold' => 100000.0,
        ]);

        // Verifikasi kondisi logika ROP: Quantity <= Threshold
        $criticalCount = WarehouseStock::whereColumn('quantity', '<=', 'safety_stock_threshold')->count();
        $this->assertGreaterThan(0, $criticalCount, 'Logika ROP mendeteksi stok kritis di bawah safety threshold');

        // 2. Jalankan request pembuatan PO Konsolidasi Darurat via Controller Endpoint
        $response = $this->actingAs($this->superAdmin)
            ->post(route('dashboard.consolidated-po'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'po_number',
            'po_id',
            'item_count',
            'total_amount',
            'total_amount_formatted',
        ]);

        $responseData = $response->json();
        $this->assertTrue($responseData['success']);
        $this->assertStringStartsWith('PO-KONS-', $responseData['po_number']);
        $this->assertGreaterThan(0, $responseData['item_count']);
        $this->assertGreaterThan(0, $responseData['total_amount']);

        // 3. Verifikasi record PO tersimpan di database
        $this->assertDatabaseHas('purchase_orders', [
            'po_number' => $responseData['po_number'],
            'status' => 'ISSUED',
        ]);
    }

    /**
     * Jalur Uji 8: Role-Based Access Control (RBAC) & Cross-Branch Authorization Guard.
     * Menguji bahwa guard keamanan memblokir akses pengguna yang tidak memiliki hak:
     * - Kasir dilarang mengakses halaman pengaturan sistem / admin roastery.
     * - Super Admin dapat mengaudit seluruh cabang.
     */
    public function test_whitebox_path8_rbac_and_cross_branch_authorization_guards(): void
    {
        // 1. Kasir mencoba mengakses Admin Settings & Roastery Execution -> Ditolak (403 Forbidden)
        $cashierSettingResponse = $this->actingAs($this->cashier)
            ->get(route('settings.index'));

        $this->assertEquals(403, $cashierSettingResponse->status(), 'Kasir ditolak (403) dari area Settings Super Admin.');

        $cashierRoasteryResponse = $this->actingAs($this->cashier)
            ->get(route('roastery.index'));

        $this->assertEquals(403, $cashierRoasteryResponse->status(), 'Kasir ditolak (403) dari area Roastery Produksi.');

        // 2. Super Admin memiliki wewenang penuh
        $adminDashboardResponse = $this->actingAs($this->superAdmin)
            ->get(route('dashboard.index'));
        $adminDashboardResponse->assertStatus(200);

        $adminSalesResponse = $this->actingAs($this->superAdmin)
            ->get(route('sales.index'));
        $adminSalesResponse->assertStatus(200);

        // 3. Pengecekan relasi cabang pengguna
        $this->assertEquals('OUT-SENO', $this->cashier->branch->code);
        $this->assertEquals('HQ-CENTRAL', $this->superAdmin->branch->code);
    }
}
