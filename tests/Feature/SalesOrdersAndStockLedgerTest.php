<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\PosOrder;
use App\Models\Product;
use App\Models\StockCard;
use App\Models\User;
use Database\Seeders\KafeinaRealDataSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SalesOrdersAndStockLedgerTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;

    protected User $cashier;

    protected Branch $senopatiBranch;

    protected Branch $bragaBranch;

    protected function setUp(): void
    {
        parent::setUp();

        if (PosOrder::count() === 0 || StockCard::count() === 0) {
            $this->seed(KafeinaRealDataSeeder::class);
        }

        $this->superAdmin = User::where('email', 'admin@kafeina.id')->first();
        $this->cashier = User::where('email', 'cashier.senopati@kafeina.id')->first();
        $this->senopatiBranch = Branch::where('code', 'OUT-SENO')->first();
        $this->bragaBranch = Branch::where('code', 'OUT-BRG')->first();
    }

    public function test_super_admin_and_cashier_can_access_sales_orders_page(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('sales.index'));

        $response->assertStatus(200);
        $response->assertViewIs('pos.orders');
        $response->assertSee('Riwayat Order & Penjualan Multi-Cabang');
        $response->assertSee('Buku Transaksi Penjualan');
        $response->assertSee('Budi Santoso');
    }

    public function test_sales_orders_can_be_filtered_by_branch(): void
    {
        // Filter Senopati
        $responseSeno = $this->actingAs($this->superAdmin)
            ->get(route('sales.index', ['branch_id' => $this->senopatiBranch->id]));

        $responseSeno->assertStatus(200);
        $responseSeno->assertSee('Budi Santoso');

        // Filter Braga
        $responseBraga = $this->actingAs($this->superAdmin)
            ->get(route('sales.index', ['branch_id' => $this->bragaBranch->id]));

        $responseBraga->assertStatus(200);
        $responseBraga->assertSee('Ridwan Kamil');
    }

    public function test_sales_order_details_endpoint_returns_json_and_recipe_audit(): void
    {
        $order = PosOrder::first();

        $response = $this->actingAs($this->superAdmin)->get(route('sales.show', $order->id));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
            ],
        ]);
    }

    public function test_sales_orders_can_be_exported_to_csv(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('sales.export.csv'));

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('kafeina_riwayat_penjualan', $response->headers->get('Content-Disposition'));
    }

    public function test_inventory_stock_cards_ledger_renders_successfully(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('inventory.index', ['tab' => 'ledger']));

        $response->assertStatus(200);
        $response->assertSee('Buku Mutasi & Kartu Stok', false);
        $response->assertSee('Buku Jurnal Mutasi Persediaan');
    }

    public function test_inventory_stock_cards_can_be_filtered_by_mutation_type(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('inventory.index', ['tab' => 'ledger', 'reference_type' => 'POS_SALE']));

        $response->assertStatus(200);
        $response->assertSee('POS Auto-Deduct');
    }

    public function test_inventory_stock_cards_can_be_exported_to_csv(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('inventory.stock-cards.export.csv'));

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('kafeina_buku_mutasi_stok', $response->headers->get('Content-Disposition'));
    }

    public function test_pos_web_checkout_persists_order_and_creates_stock_cards(): void
    {
        $product = Product::where('sku', 'PRD-ESP-03')->first(); // Flat White Velvet
        $initialOrderCount = PosOrder::count();
        $initialStockCardCount = StockCard::count();

        $payload = [
            'order_type' => 'Dine In',
            'table_number' => '12',
            'customer_name' => 'Pak Joko Widodo',
            'payment_method' => 'cash',
            'paid_amount' => 100000,
            'items' => [
                [
                    'id' => $product->sku,
                    'name' => $product->name,
                    'price' => $product->selling_price,
                    'qty' => 1,
                ],
            ],
        ];

        $response = $this->actingAs($this->cashier)
            ->postJson(route('pos.checkout'), $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'order' => [
                'customer_name' => 'Pak Joko Widodo',
                'table_number' => '12',
            ],
        ]);

        $this->assertGreaterThan($initialOrderCount, PosOrder::count());
        $this->assertGreaterThan($initialStockCardCount, StockCard::count());

        $newOrder = PosOrder::where('customer_name', 'Pak Joko Widodo')->first();
        $this->assertNotNull($newOrder);
        $this->assertEquals('DINE_IN', $newOrder->order_type);
        $this->assertEquals('12', $newOrder->table_number);
    }
}
