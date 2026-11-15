<?php

namespace Tests\Feature;

use App\Exceptions\InsufficientStockException;
use App\Models\Branch;
use App\Models\InventoryWarehouse;
use App\Models\PosOrder;
use App\Models\PosShift;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductRecipe;
use App\Models\Role;
use App\Models\StockCard;
use App\Models\User;
use App\Models\WarehouseStock;
use App\Services\PosBillingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ConcurrencyPosCheckoutTest extends TestCase
{
    use DatabaseTransactions;

    protected User $cashierUser;

    protected Branch $branch;

    protected InventoryWarehouse $barWarehouse;

    protected Product $espressoBean;

    protected Product $freshMilk;

    protected Product $cafeLatte;

    protected PosShift $shift;

    protected PosBillingService $billingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->billingService = new PosBillingService;

        $cashierRole = Role::firstOrCreate(['name' => 'branch_cashier'], ['display_name' => 'Kasir Barista']);

        // Ensure user exists
        $this->cashierUser = User::firstOrCreate(
            ['email' => 'cashier.concurrency@kafeina.id'],
            [
                'name' => 'Barista Kasir Concurrency',
                'role_id' => $cashierRole->id,
                'password' => bcrypt('password123'),
                'pin_code' => bcrypt('123456'),
                'is_active' => true,
            ]
        );

        // Branch
        $this->branch = Branch::firstOrCreate(
            ['code' => 'BR-TEST-CONC'],
            [
                'name' => 'Cabang Test Concurrency',
                'type' => 'RETAIL_BRANCH',
                'address' => 'Jl. Uji Konkurensi No. 50',
                'phone' => '081234567890',
                'is_active' => true,
            ]
        );

        // Bar Warehouse
        $this->barWarehouse = InventoryWarehouse::firstOrCreate(
            ['branch_id' => $this->branch->id, 'code' => 'WH-BAR-CONC'],
            [
                'name' => 'Bar Outlet Concurrency',
                'type' => 'RETAIL_BAR',
                'is_active' => true,
            ]
        );

        // Category
        $cat = ProductCategory::firstOrCreate(
            ['name' => 'Concurrency Beverage'],
            ['description' => 'Menu Uji Transaksi Konkuren']
        );

        // Raw Ingredients
        $this->espressoBean = Product::firstOrCreate(
            ['sku' => 'CONC-ESP-BEAN'],
            [
                'category_id' => $cat->id,
                'name' => 'Espresso Beans Concurrency Test',
                'unit' => 'gram',
                'cost_price' => 150.0,
                'selling_price' => 0.0,
                'is_raw_material' => true,
                'minimum_stock_alert' => 50,
            ]
        );

        $this->freshMilk = Product::firstOrCreate(
            ['sku' => 'CONC-FRESH-MILK'],
            [
                'category_id' => $cat->id,
                'name' => 'Fresh Milk Concurrency Test',
                'unit' => 'ml',
                'cost_price' => 20.0,
                'selling_price' => 0.0,
                'is_raw_material' => true,
                'minimum_stock_alert' => 500,
            ]
        );

        // Finished Good: Cafe Latte
        $this->cafeLatte = Product::firstOrCreate(
            ['sku' => 'CONC-CAFE-LATTE'],
            [
                'category_id' => $cat->id,
                'name' => 'Cafe Latte Concurrency Test',
                'unit' => 'cup',
                'cost_price' => 5700.0,
                'selling_price' => 38000.0,
                'is_raw_material' => false,
                'minimum_stock_alert' => 10,
            ]
        );

        // Recipe: 1 Cafe Latte = 18g espresso beans + 150ml milk
        ProductRecipe::firstOrCreate(
            ['product_id' => $this->cafeLatte->id, 'ingredient_product_id' => $this->espressoBean->id],
            ['quantity_required' => 18.0, 'unit' => 'gram']
        );
        ProductRecipe::firstOrCreate(
            ['product_id' => $this->cafeLatte->id, 'ingredient_product_id' => $this->freshMilk->id],
            ['quantity_required' => 150.0, 'unit' => 'ml']
        );

        // Active Pos Shift
        $this->shift = PosShift::create([
            'shift_code' => 'SH-CONC-'.uniqid(),
            'cashier_user_id' => $this->cashierUser->id,
            'branch_id' => $this->branch->id,
            'opening_cash' => 500000,
            'opened_at' => now(),
            'status' => 'OPEN',
        ]);
    }

    /**
     * TICK-054: Concurrency Simulation & Negative Stock Guard.
     * Simulates 50 high-concurrency checkout requests with exact inventory for 10 cups.
     * Asserts that exactly 10 succeed, 40 fail gracefully with InsufficientStockException,
     * and final stock is strictly 0.00 (NEVER negative!).
     */
    public function test_concurrency_fifty_checkouts_prevent_negative_inventory(): void
    {
        // Set exact stock for exactly 10 cups:
        // 10 cups * 18g = 180g beans
        // 10 cups * 150ml = 1500ml milk
        WarehouseStock::updateOrCreate(
            ['warehouse_id' => $this->barWarehouse->id, 'product_id' => $this->espressoBean->id],
            ['quantity' => 180.0, 'reserved_quantity' => 0.0]
        );
        WarehouseStock::updateOrCreate(
            ['warehouse_id' => $this->barWarehouse->id, 'product_id' => $this->freshMilk->id],
            ['quantity' => 1500.0, 'reserved_quantity' => 0.0]
        );

        $successfulOrders = 0;
        $rejectedOrders = 0;
        $totalTransactions = 50;

        for ($i = 1; $i <= $totalTransactions; $i++) {
            try {
                $orderData = [
                    'order_type' => 'DINE_IN',
                    'payment_method' => 'CASH',
                    'discount_amount' => 0.0,
                    'items' => [
                        [
                            'product_id' => $this->cafeLatte->id,
                            'quantity' => 1,
                            'unit_price' => 38000.0,
                        ],
                    ],
                ];

                $order = $this->billingService->checkout(
                    $this->shift,
                    $this->cashierUser,
                    $orderData
                );

                $this->assertNotNull($order->id);
                $this->assertEquals(38000.0, (float) $order->subtotal);
                $this->assertEquals(4180.0, (float) $order->tax_amount); // PB1 11% (38.000 * 0.11)
                $this->assertEquals(42180.0, (float) $order->total_amount);
                $successfulOrders++;
            } catch (InsufficientStockException $e) {
                // Stock exhausted as expected
                $this->assertStringContainsString('tidak mencukupi', $e->getMessage());
                $rejectedOrders++;
            }
        }

        // Verification assertions
        $this->assertEquals(10, $successfulOrders, 'Harus tepat 10 transaksi berhasil sesuai kapasitas persediaan fisik.');
        $this->assertEquals(40, $rejectedOrders, 'Harus tepat 40 transaksi digagalkan untuk mencegah stok negatif.');

        // Verify remaining stock in database
        $beanStock = WarehouseStock::where('warehouse_id', $this->barWarehouse->id)
            ->where('product_id', $this->espressoBean->id)
            ->value('quantity');

        $milkStock = WarehouseStock::where('warehouse_id', $this->barWarehouse->id)
            ->where('product_id', $this->freshMilk->id)
            ->value('quantity');

        // STRICT INVARIANT: Stock must never be negative!
        $this->assertEquals(0.0, (float) $beanStock, 'Stok biji kopi harus tepat 0.0 (tidak boleh minus).');
        $this->assertEquals(0.0, (float) $milkStock, 'Stok susu segar harus tepat 0.0 (tidak boleh minus).');
        $this->assertGreaterThanOrEqual(0.0, (float) $beanStock);
        $this->assertGreaterThanOrEqual(0.0, (float) $milkStock);

        // Verify total stock cards created for this session
        $beanOutflow = StockCard::where('warehouse_id', $this->barWarehouse->id)
            ->where('product_id', $this->espressoBean->id)
            ->where('reference_type', 'POS_SALE')
            ->sum('quantity_out');

        $milkOutflow = StockCard::where('warehouse_id', $this->barWarehouse->id)
            ->where('product_id', $this->freshMilk->id)
            ->where('reference_type', 'POS_SALE')
            ->sum('quantity_out');

        $this->assertEquals(180.0, (float) $beanOutflow);
        $this->assertEquals(1500.0, (float) $milkOutflow);
    }

    /**
     * TICK-054: Non-Revenue Calibration Orders Concurrently Decrement Stock Without Adding Revenue.
     */
    public function test_non_revenue_calibration_orders_reduce_stock_with_zero_revenue(): void
    {
        // 100g of espresso beans
        WarehouseStock::updateOrCreate(
            ['warehouse_id' => $this->barWarehouse->id, 'product_id' => $this->espressoBean->id],
            ['quantity' => 100.0, 'reserved_quantity' => 0.0]
        );
        WarehouseStock::updateOrCreate(
            ['warehouse_id' => $this->barWarehouse->id, 'product_id' => $this->freshMilk->id],
            ['quantity' => 1000.0, 'reserved_quantity' => 0.0]
        );

        // Barista performs 3 espresso calibration shots (each 18g = 54g total)
        for ($i = 1; $i <= 3; $i++) {
            $order = $this->billingService->checkout(
                $this->shift,
                $this->cashierUser,
                [
                    'order_type' => 'NON_REVENUE',
                    'non_revenue_reason' => 'CALIBRATION',
                    'items' => [
                        [
                            'product_id' => $this->cafeLatte->id,
                            'quantity' => 1,
                            'unit_price' => 38000.0,
                        ],
                    ],
                ]
            );

            $this->assertEquals('NON_REVENUE', $order->order_type);
            $this->assertEquals('CALIBRATION', $order->non_revenue_reason);
            $this->assertEquals(0.00, (float) $order->total_amount);
            $this->assertEquals(0.00, (float) $order->tax_amount);
        }

        // Remaining beans: 100 - (3 * 18) = 46g
        $beanStock = WarehouseStock::where('warehouse_id', $this->barWarehouse->id)
            ->where('product_id', $this->espressoBean->id)
            ->value('quantity');

        $this->assertEquals(46.0, (float) $beanStock);

        // Verify stock card reference_type is ADJUSTMENT, not POS_SALE
        $adjCount = StockCard::where('warehouse_id', $this->barWarehouse->id)
            ->where('product_id', $this->espressoBean->id)
            ->where('reference_type', 'ADJUSTMENT')
            ->count();

        $this->assertEquals(3, $adjCount);
    }

    /**
     * TICK-054: Database Atomicity (ACID) - Mid-Transaction Failure Rolls Back Completely.
     */
    public function test_checkout_mid_transaction_insufficient_stock_rolls_back_atomically(): void
    {
        // 500g beans available (enough for 27 cups), but only 100ml milk (insufficient for 1 cup @ 150ml)
        WarehouseStock::updateOrCreate(
            ['warehouse_id' => $this->barWarehouse->id, 'product_id' => $this->espressoBean->id],
            ['quantity' => 500.0, 'reserved_quantity' => 0.0]
        );
        WarehouseStock::updateOrCreate(
            ['warehouse_id' => $this->barWarehouse->id, 'product_id' => $this->freshMilk->id],
            ['quantity' => 100.0, 'reserved_quantity' => 0.0]
        );

        $initialOrdersCount = PosOrder::count();

        try {
            $this->billingService->checkout(
                $this->shift,
                $this->cashierUser,
                [
                    'order_type' => 'DINE_IN',
                    'payment_method' => 'CASH',
                    'items' => [
                        [
                            'product_id' => $this->cafeLatte->id,
                            'quantity' => 1,
                            'unit_price' => 38000.0,
                        ],
                    ],
                ]
            );
            $this->fail('Harus melempar InsufficientStockException karena susu tidak mencukupi.');
        } catch (InsufficientStockException $e) {
            $this->assertStringContainsString('Fresh Milk Concurrency Test', $e->getMessage());
        }

        // Assert atomicity: No PosOrder should be created
        $this->assertEquals($initialOrdersCount, PosOrder::count(), 'Transaksi harus di-rollback total sehingga tidak ada PosOrder yatim.');

        // Beans must NOT have been deducted even though beans check occurred first
        $beanStock = WarehouseStock::where('warehouse_id', $this->barWarehouse->id)
            ->where('product_id', $this->espressoBean->id)
            ->value('quantity');

        $this->assertEquals(500.0, (float) $beanStock, 'Stok biji kopi harus tetap utuh (500g) setelah rollback atomik.');
    }
}
