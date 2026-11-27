<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\InventoryWarehouse;
use App\Models\Product;
use App\Models\ProductModifier;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\WarehouseStock;
use Database\Seeders\KafeinaRealDataSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CrossModuleSynergyTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;

    protected User $cashier;

    protected InventoryWarehouse $whTransit;

    protected InventoryWarehouse $whBarSeno;

    protected function setUp(): void
    {
        parent::setUp();

        if (Branch::where('code', 'OUT-SENO')->doesntExist()) {
            $this->seed(KafeinaRealDataSeeder::class);
        }

        $this->superAdmin = User::where('email', 'admin@kafeina.id')->firstOrFail();
        $this->cashier = User::where('email', 'cashier.senopati@kafeina.id')->firstOrFail();
        $this->whTransit = InventoryWarehouse::where('code', 'WH-TRANSIT')->firstOrFail();
        $this->whBarSeno = InventoryWarehouse::where('code', 'WH-BAR-SENO')->firstOrFail();
    }

    public function test_dashboard_consolidated_po_converts_ml_deficit_to_liters_and_receives_accurately(): void
    {
        // Set semua stok di atas safety stock dulu, lalu sisakan 1 bahan cair (Fresh Milk) defisit 5.000 ml (5 liter)
        WarehouseStock::query()->update(['quantity' => 999999]);

        $freshMilk = Product::where('sku', 'RAW-MILK-FRESH')->firstOrFail();
        WarehouseStock::updateOrCreate(
            ['warehouse_id' => $this->whBarSeno->id, 'product_id' => $freshMilk->id],
            ['quantity' => 1000, 'safety_stock_threshold' => 6000]
        );

        // Pastikan WH-TRANSIT bersaldo 0 TIDAK ikut memicu PO darurat atau alert
        WarehouseStock::updateOrCreate(
            ['warehouse_id' => $this->whTransit->id, 'product_id' => $freshMilk->id],
            ['quantity' => 0, 'safety_stock_threshold' => 0]
        );

        $res = $this->actingAs($this->superAdmin)->postJson(route('dashboard.consolidated-po'));
        $res->assertOk()->assertJson(['success' => true]);

        $poId = (int) $res->json('po_id');
        $po = PurchaseOrder::with('items')->findOrFail($poId);
        $item = $po->items->firstWhere('product_id', $freshMilk->id);

        $this->assertNotNull($item);
        // Defisit 5.000 ml -> harus dipesan sebagai 5 liter @ Rp 25.000/liter = Rp 125.000 (BUKAN 5.000 x Rp 1.000!)
        $this->assertEquals(5.0, (float) $item->quantity_kg);
        $this->assertEquals(25000.0, (float) $item->unit_price);

        // Terima PO konsolidasi via /procurement/po/{id}/receive -> harus masuk tepat +5.000 ml (BUKAN 5.000.000 ml!)
        $receive = $this->actingAs($this->superAdmin)->postJson(route('procurement.po.receive', $poId), [
            'warehouse_id' => $this->whBarSeno->id,
        ]);
        $receive->assertOk()->assertJson(['success' => true]);

        $finalMilk = (float) WarehouseStock::where('warehouse_id', $this->whBarSeno->id)
            ->where('product_id', $freshMilk->id)
            ->value('quantity');

        $this->assertEquals(6000.0, $finalMilk);
    }

    public function test_transit_warehouse_is_excluded_from_inventory_and_dashboard_rop_alerts(): void
    {
        $houseBeans = Product::where('sku', 'RAW-RB-HOUSE')->firstOrFail();

        WarehouseStock::updateOrCreate(
            ['warehouse_id' => $this->whTransit->id, 'product_id' => $houseBeans->id],
            ['quantity' => 0, 'safety_stock_threshold' => 0]
        );

        $invRes = $this->actingAs($this->superAdmin)->get(route('inventory.index'));
        $invRes->assertOk();
        $invAlerts = $invRes->viewData('safetyStockAlerts');
        foreach ($invAlerts as $alert) {
            $this->assertNotEquals($this->whTransit->name, $alert['warehouse'] ?? '');
        }

        $dashRes = $this->actingAs($this->superAdmin)->get(route('dashboard.index'));
        $dashRes->assertOk();
        $dashAlerts = $dashRes->viewData('stockAlerts');
        foreach ($dashAlerts as $alert) {
            $this->assertNotEquals($this->whTransit->name, $alert['warehouse'] ?? '');
        }
    }

    public function test_products_modifiers_and_canonical_retail_skus_sync_directly_to_pos(): void
    {
        $customMod = ProductModifier::create([
            'name' => 'Gula Aren Organik Test Synergy',
            'group_name' => 'Pemanis Alami',
            'price' => 4500,
            'is_active' => true,
        ]);

        $posRes = $this->actingAs($this->cashier)->get(route('pos.index'));
        $posRes->assertOk();

        // Modifier baru dari /products langsung muncul di /pos
        $posRes->assertSee($customMod->name);

        // SKU Retail kanonikal PRD-RET-01 & PRD-RET-02 tidak duplikat dengan PRD-RT-01
        $products = $posRes->viewData('products');
        $skus = array_column($products, 'id');
        $this->assertContains('PRD-RET-01', $skus);
        $this->assertContains('PRD-RET-02', $skus);
        $this->assertNotContains('PRD-RT-01', $skus);
    }

    public function test_sidebar_logo_routes_to_authorized_home_for_cashier_and_has_valid_active_class(): void
    {
        $posRes = $this->actingAs($this->cashier)->get(route('pos.index'));
        $posRes->assertOk();

        // Kasir tidak boleh diarahkan ke /dashboard saat klik logo
        $posRes->assertSee('href="'.route('pos.index').'"', false);
        // Pastikan tidak ada kutip ganda rusak dari bug atribut sidebar lama
        $posRes->assertDontSee(':title="sidebarCollapsed ? \'Kasir POS (⌘1)\' : \'\'" bg-neutral-100', false);
    }
}
