<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Bom;
use App\Models\Branch;
use App\Models\GreenBeanLot;
use App\Models\InventoryWarehouse;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\RoastingProfile;
use App\Models\Supplier;
use App\Models\User;
use App\Models\WarehouseStock;
use Database\Seeders\KafeinaRealDataSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SupplyChainEndToEndHardeningTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;

    protected User $roaster;

    protected User $warehouseStaff;

    protected Branch $centralBranch;

    protected Branch $senopatiBranch;

    protected InventoryWarehouse $whRawHq;

    protected InventoryWarehouse $whRoastHq;

    protected InventoryWarehouse $whTransit;

    protected InventoryWarehouse $whBarSeno;

    protected Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        if (Product::where('sku', 'RAW-GB-GAYO')->doesntExist()) {
            $this->seed(KafeinaRealDataSeeder::class);
        }

        $this->superAdmin = User::where('email', 'admin@kafeina.id')->firstOrFail();
        $this->roaster = User::where('email', 'roaster@kafeina.id')->firstOrFail();
        $this->warehouseStaff = User::where('email', 'warehouse@kafeina.id')->firstOrFail();

        $this->centralBranch = Branch::where('code', 'HQ-CENTRAL')->firstOrFail();
        $this->senopatiBranch = Branch::where('code', 'OUT-SENO')->firstOrFail();

        $this->whRawHq = InventoryWarehouse::where('code', 'WH-RAW-HQ')->firstOrFail();
        $this->whRoastHq = InventoryWarehouse::where('code', 'WH-ROAST-HQ')->firstOrFail();
        $this->whTransit = InventoryWarehouse::where('code', 'WH-TRANSIT')->firstOrFail();
        $this->whBarSeno = InventoryWarehouse::where('code', 'WH-BAR-SENO')->firstOrFail();

        $this->supplier = Supplier::firstOrFail();
    }

    public function test_procurement_supports_non_coffee_po_and_atomic_goods_receipt(): void
    {
        $oatMilk = Product::where('sku', 'RAW-MILK-OAT')->firstOrFail();
        $greenBean = Product::where('sku', 'RAW-GB-GAYO')->firstOrFail();

        // 1. Halaman /procurement menampilkan bahan kopi dan non-kopi
        $page = $this->actingAs($this->superAdmin)->get(route('procurement.index'));
        $page->assertOk();
        $page->assertSee('RAW-MILK-OAT');
        $page->assertSee('PKG-CUP-HOT8');

        // 2. Terbitkan PO Non-Kopi (10 liter Oat Milk @ Rp 52.000/liter)
        $poRes = $this->actingAs($this->superAdmin)->postJson(route('procurement.po.store'), [
            'supplier_id' => $this->supplier->id,
            'product_id' => $oatMilk->id,
            'weight_kg' => 10,
            'price_per_kg' => 52000,
            'notes' => 'Restock Oat Milk Senopati',
        ]);

        $poRes->assertOk()->assertJson(['success' => true]);
        $poId = (int) $poRes->json('po.id');

        $stockBefore = (float) WarehouseStock::where('warehouse_id', $this->whBarSeno->id)
            ->where('product_id', $oatMilk->id)
            ->value('quantity');

        // 3. Terima Barang (Goods Receipt) -> konversi 10 liter = +10.000 ml ke WH-BAR-SENO
        $receiveRes = $this->actingAs($this->superAdmin)->postJson(route('procurement.po.receive', $poId), [
            'warehouse_id' => $this->whBarSeno->id,
            'notes' => 'Diterima dingin 4C',
        ]);

        $receiveRes->assertOk()->assertJson(['success' => true]);

        $stockAfter = (float) WarehouseStock::where('warehouse_id', $this->whBarSeno->id)
            ->where('product_id', $oatMilk->id)
            ->value('quantity');

        $this->assertEquals($stockBefore + 10000.0, $stockAfter);
        $this->assertEquals('COMPLETED', PurchaseOrder::findOrFail($poId)->status);
        $this->assertDatabaseHas('stock_cards', [
            'warehouse_id' => $this->whBarSeno->id,
            'product_id' => $oatMilk->id,
            'reference_type' => 'PO_RECEIPT',
            'reference_id' => $poId,
            'quantity_in' => 10000.0,
            'cost_per_unit' => 52.0,
        ]);

        // 4. Tolak penerimaan ganda pada PO yang sudah COMPLETED
        $doubleReceive = $this->actingAs($this->superAdmin)->postJson(route('procurement.po.receive', $poId), [
            'warehouse_id' => $this->whBarSeno->id,
        ]);
        $doubleReceive->assertStatus(422)->assertJson(['success' => false]);

        // 5. PO Green Beans dilarang bypass Goods Receipt (wajib lewat QC Lab)
        $gbPoRes = $this->actingAs($this->superAdmin)->postJson(route('procurement.po.store'), [
            'supplier_id' => $this->supplier->id,
            'product_id' => $greenBean->id,
            'weight_kg' => 30,
            'price_per_kg' => 135000,
        ]);
        $gbPoRes->assertOk();
        $gbPoId = (int) $gbPoRes->json('po.id');

        $bypassRes = $this->actingAs($this->superAdmin)->postJson(route('procurement.po.receive', $gbPoId), [
            'warehouse_id' => $this->whRawHq->id,
        ]);
        $bypassRes->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_qc_lab_accepted_syncs_real_cost_and_stock_to_central_raw_warehouse(): void
    {
        $gayoGreen = Product::where('sku', 'RAW-GB-GAYO')->firstOrFail();

        $poRes = $this->actingAs($this->superAdmin)->postJson(route('procurement.po.store'), [
            'supplier_id' => $this->supplier->id,
            'product_id' => $gayoGreen->id,
            'weight_kg' => 40,
            'price_per_kg' => 145000,
        ]);
        $poRes->assertOk();
        $poId = (int) $poRes->json('po.id');

        $rawBefore = (float) WarehouseStock::where('warehouse_id', $this->whRawHq->id)
            ->where('product_id', $gayoGreen->id)
            ->value('quantity');

        $qcRes = $this->actingAs($this->superAdmin)->postJson(route('qc-lab.store'), [
            'po_id' => $poId,
            'origin_code' => 'GYO',
            'variety' => 'Green Beans Aceh Gayo Honey Process',
            'process_type' => 'Honey',
            'harvest_year' => 2026,
            'weight_kg' => 40,
            'moisture_content' => 11.1,
            'bulk_density_g_l' => 715,
            'primary_defects' => 0,
            'secondary_defects' => 1,
            'sca_cupping_score' => 86.75,
        ]);

        $qcRes->assertOk()->assertJson(['success' => true]);

        $lotId = (int) $qcRes->json('qc.lot.id');
        $lot = GreenBeanLot::findOrFail($lotId);

        // HPP per gram harus sesuai PO (Rp 145.000/kg -> Rp 145/g), bukan hardcode Rp 120
        $this->assertEquals(145.0, (float) $lot->price_per_gram);
        $this->assertEquals(40000.0, (float) $lot->current_weight_grams);
        $this->assertEquals('COMPLETED', PurchaseOrder::findOrFail($poId)->status);

        $rawAfter = (float) WarehouseStock::where('warehouse_id', $this->whRawHq->id)
            ->where('product_id', $gayoGreen->id)
            ->value('quantity');

        $this->assertEquals($rawBefore + 40000.0, $rawAfter);
        $this->assertDatabaseHas('stock_cards', [
            'warehouse_id' => $this->whRawHq->id,
            'product_id' => $gayoGreen->id,
            'lot_id' => $lot->id,
            'reference_type' => 'PO_RECEIPT',
            'reference_id' => $poId,
            'quantity_in' => 40000.0,
            'cost_per_unit' => 145.0,
        ]);

        // Tolak inspeksi ulang PO yang sudah COMPLETED
        $duplicateQc = $this->actingAs($this->superAdmin)->postJson(route('qc-lab.store'), [
            'po_id' => $poId,
            'origin_code' => 'GYO',
            'variety' => 'Duplicate Check',
            'process_type' => 'Honey',
            'harvest_year' => 2026,
            'weight_kg' => 40,
            'moisture_content' => 11.1,
            'bulk_density_g_l' => 715,
            'primary_defects' => 0,
            'secondary_defects' => 1,
            'sca_cupping_score' => 86.75,
        ]);
        $duplicateQc->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_roastery_validates_lot_stock_blocks_double_roast_and_packs_retail_bom(): void
    {
        $gayoGreen = Product::where('sku', 'RAW-GB-GAYO')->firstOrFail();
        $gayoRoasted = Product::where('sku', 'RAW-RB-GAYO')->firstOrFail();
        $pouch = Product::where('sku', 'PKG-POUCH-250G')->firstOrFail();
        $label = Product::where('sku', 'PKG-LABEL-250G')->firstOrFail();
        $retailGayo = Product::where('sku', 'PRD-RET-01')->firstOrFail();
        $profile = RoastingProfile::firstOrFail();

        $lot = GreenBeanLot::create([
            'lot_number' => 'GB-GYO-TEST-'.rand(1000, 9999),
            'purchase_order_id' => PurchaseOrder::first()?->id,
            'variety' => 'Aceh Gayo Honey Test',
            'origin_region' => 'Aceh Gayo',
            'process_type' => 'HONEY',
            'harvest_year' => 2026,
            'initial_weight_grams' => 15000,
            'current_weight_grams' => 15000,
            'price_per_gram' => 140.0,
        ]);

        // 1. Tolak WO melebihi sisa stok lot (minta 20 kg padahal sisa 15 kg)
        $overWo = $this->actingAs($this->roaster)->postJson(route('roastery.wo.store'), [
            'lot_id' => $lot->id,
            'profile_id' => $profile->id,
            'green_weight_kg' => 20,
        ]);
        $overWo->assertStatus(422)->assertJson(['success' => false]);

        // 2. Buat WO valid 10 kg
        $woRes = $this->actingAs($this->roaster)->postJson(route('roastery.wo.store'), [
            'lot_id' => $lot->id,
            'profile_id' => $profile->id,
            'green_weight_kg' => 10,
        ]);
        $woRes->assertOk()->assertJson(['success' => true]);
        $woId = (int) $woRes->json('work_order.id');

        $roastBefore = (float) WarehouseStock::where('warehouse_id', $this->whRoastHq->id)
            ->where('product_id', $gayoRoasted->id)
            ->value('quantity');

        // 3. Eksekusi Sangrai dengan target SKU RAW-RB-GAYO (10 kg -> 8.5 kg)
        $execRes = $this->actingAs($this->roaster)->postJson(route('roastery.execute'), [
            'wo_id' => $woId,
            'green_weight_kg' => 10,
            'roasted_weight_kg' => 8.5,
            'target_product_id' => $gayoRoasted->id,
        ]);

        $execRes->assertOk()->assertJson([
            'success' => true,
            'output_sku' => 'RAW-RB-GAYO',
        ]);

        // HPP Riil: (140 * (10000 / 8500)) + 15 = 164.71 + 15 = 179.71
        $expectedHpp = round((140.0 * (10000.0 / 8500.0)) + 15.0, 2);
        $this->assertEquals($expectedHpp, (float) $gayoRoasted->fresh()->cost_price);

        $roastAfter = (float) WarehouseStock::where('warehouse_id', $this->whRoastHq->id)
            ->where('product_id', $gayoRoasted->id)
            ->value('quantity');
        $this->assertEquals($roastBefore + 8500.0, $roastAfter);

        // 4. Tolak eksekusi ulang WO yang sudah COMPLETED
        $doubleExec = $this->actingAs($this->roaster)->postJson(route('roastery.execute'), [
            'wo_id' => $woId,
            'green_weight_kg' => 10,
            'roasted_weight_kg' => 8.5,
            'target_product_id' => $gayoRoasted->id,
        ]);
        $doubleExec->assertStatus(422)->assertJson(['success' => false]);

        // 5. Kemas Retail 250g berdasarkan BOM PRD-RET-01 (2 pack)
        $bom = Bom::where('product_id', $retailGayo->id)->firstOrFail();

        // Pastikan kemasan pouch & label tersedia di WH-ROAST-HQ
        WarehouseStock::updateOrCreate(
            ['warehouse_id' => $this->whRoastHq->id, 'product_id' => $pouch->id],
            ['quantity' => 50, 'safety_stock_threshold' => 10]
        );
        WarehouseStock::updateOrCreate(
            ['warehouse_id' => $this->whRoastHq->id, 'product_id' => $label->id],
            ['quantity' => 50, 'safety_stock_threshold' => 10]
        );

        $retailBefore = (float) (WarehouseStock::where('warehouse_id', $this->whRoastHq->id)
            ->where('product_id', $retailGayo->id)
            ->value('quantity') ?? 0);

        $packRes = $this->actingAs($this->roaster)->postJson(route('roastery.boms.pack', $bom->id), [
            'quantity_packs' => 2,
            'notes' => 'Test kemas 2 pouch',
        ]);

        $packRes->assertOk()->assertJson(['success' => true]);

        $this->assertEquals($roastAfter - 500.0, (float) WarehouseStock::where('warehouse_id', $this->whRoastHq->id)->where('product_id', $gayoRoasted->id)->value('quantity'));
        $this->assertEquals(48.0, (float) WarehouseStock::where('warehouse_id', $this->whRoastHq->id)->where('product_id', $pouch->id)->value('quantity'));
        $this->assertEquals(48.0, (float) WarehouseStock::where('warehouse_id', $this->whRoastHq->id)->where('product_id', $label->id)->value('quantity'));
        $this->assertEquals($retailBefore + 2.0, (float) WarehouseStock::where('warehouse_id', $this->whRoastHq->id)->where('product_id', $retailGayo->id)->value('quantity'));
    }

    public function test_logistics_mr_dispatch_transit_isolation_and_receipt_confirmation(): void
    {
        $houseBeans = Product::where('sku', 'RAW-RB-HOUSE')->firstOrFail();

        // Pastikan saldo awal WH-ROAST-HQ cukup
        $sourceStock = WarehouseStock::firstOrCreate(
            ['warehouse_id' => $this->whRoastHq->id, 'product_id' => $houseBeans->id],
            ['quantity' => 0, 'safety_stock_threshold' => 5000]
        );
        if ((float) $sourceStock->quantity < 5000) {
            $sourceStock->increment('quantity', 10000);
        }

        $sourceBefore = (float) $sourceStock->fresh()->quantity;
        $transitBefore = (float) (WarehouseStock::where('warehouse_id', $this->whTransit->id)->where('product_id', $houseBeans->id)->value('quantity') ?? 0);
        $destBefore = (float) (WarehouseStock::where('warehouse_id', $this->whBarSeno->id)->where('product_id', $houseBeans->id)->value('quantity') ?? 0);

        // 1. Buat MR dengan product_id eksak (2000 gram)
        $mrRes = $this->actingAs($this->warehouseStaff)->postJson(route('logistics.mr.store'), [
            'from_branch_id' => $this->senopatiBranch->id,
            'to_branch_id' => $this->centralBranch->id,
            'product_id' => $houseBeans->id,
            'quantity' => 2000,
            'notes' => 'Pasokan uji logistik',
        ]);
        $mrRes->assertOk()->assertJson(['success' => true]);
        $mrId = (int) $mrRes->json('material_request.id');
        $this->assertEquals($this->warehouseStaff->id, (int) $mrRes->json('material_request.requested_by_user_id'));

        // 2. Approve MR
        $approveRes = $this->actingAs($this->warehouseStaff)->postJson(route('logistics.material-requests.approve', $mrId));
        $approveRes->assertOk()->assertJson(['success' => true]);

        // 3. Terbitkan Surat Jalan (Dispatch) -> potong WH-ROAST-HQ, isolasi ke WH-TRANSIT, quantity_sent = 2000
        $dnRes = $this->actingAs($this->warehouseStaff)->postJson(route('logistics.dn.store'), [
            'request_id' => $mrId,
            'source_warehouse_id' => $this->whRoastHq->id,
            'dest_warehouse_id' => $this->whBarSeno->id,
            'courier_name' => 'Dedi Kurniawan',
            'vehicle_number' => 'B 9281 KAF',
        ]);
        $dnRes->assertOk()->assertJson(['success' => true]);
        $dnId = (int) $dnRes->json('delivery_note.id');

        $this->assertDatabaseHas('delivery_note_items', [
            'delivery_note_id' => $dnId,
            'product_id' => $houseBeans->id,
            'quantity_sent' => 2000.0,
            'quantity_received' => 0.0,
        ]);

        $this->assertEquals($sourceBefore - 2000.0, (float) WarehouseStock::where('warehouse_id', $this->whRoastHq->id)->where('product_id', $houseBeans->id)->value('quantity'));
        $this->assertEquals($transitBefore + 2000.0, (float) WarehouseStock::where('warehouse_id', $this->whTransit->id)->where('product_id', $houseBeans->id)->value('quantity'));

        // 4. Konfirmasi Penerimaan dengan 100g rusak -> 1900g masuk WH-BAR-SENO, WH-TRANSIT turun 2000g, status RECONCILED
        $receiptRes = $this->actingAs($this->warehouseStaff)->postJson(route('logistics.receipt.confirm'), [
            'delivery_note_id' => $dnId,
            'damaged_items' => 100,
            'discrepancy_notes' => '1 kemasan bocor 100g',
        ]);
        $receiptRes->assertOk()->assertJson(['success' => true]);
        $this->assertEquals('RECONCILED', $receiptRes->json('delivery_note.status'));

        $this->assertEquals($transitBefore, (float) WarehouseStock::where('warehouse_id', $this->whTransit->id)->where('product_id', $houseBeans->id)->value('quantity'));
        $this->assertEquals($destBefore + 1900.0, (float) WarehouseStock::where('warehouse_id', $this->whBarSeno->id)->where('product_id', $houseBeans->id)->value('quantity'));

        $this->assertDatabaseHas('stock_cards', [
            'warehouse_id' => $this->whBarSeno->id,
            'product_id' => $houseBeans->id,
            'reference_type' => 'TRANSFER_RECEIVE',
            'reference_id' => $dnId,
            'quantity_in' => 1900.0,
        ]);

        // 5. Tolak konfirmasi penerimaan ganda
        $doubleReceipt = $this->actingAs($this->warehouseStaff)->postJson(route('logistics.receipt.confirm'), [
            'delivery_note_id' => $dnId,
            'damaged_items' => 0,
        ]);
        $doubleReceipt->assertStatus(422)->assertJson(['success' => false]);
    }
}
