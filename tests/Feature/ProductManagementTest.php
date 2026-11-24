<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;

    protected User $storeManager;

    protected User $headRoaster;

    protected User $cashier;

    protected ProductCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::first() ?? Branch::create([
            'code' => 'OUT-TEST-PRD',
            'name' => 'Outlet Test Product',
            'type' => 'RETAIL_BRANCH',
        ]);

        $adminRole = Role::where('name', 'super_admin')->first();
        $managerRole = Role::whereIn('name', ['store_manager', 'supervisor'])->first() ?? $adminRole;
        $roasterRole = Role::whereIn('name', ['roaster', 'head_roaster'])->first();
        $cashierRole = Role::whereIn('name', ['cashier', 'branch_cashier'])->first();

        $this->superAdmin = User::create([
            'email' => 'admin_prd_test@kafeina.test',
            'name' => 'Super Admin Test',
            'role_id' => $adminRole->id,
            'branch_id' => $branch->id,
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $this->storeManager = User::create([
            'email' => 'manager_prd_test@kafeina.test',
            'name' => 'Store Manager Test',
            'role_id' => $managerRole->id,
            'branch_id' => $branch->id,
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $this->headRoaster = User::create([
            'email' => 'roaster_prd_test@kafeina.test',
            'name' => 'Head Roaster Test',
            'role_id' => $roasterRole->id,
            'branch_id' => $branch->id,
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $this->cashier = User::create([
            'email' => 'cashier_prd_test@kafeina.test',
            'name' => 'Cashier Test',
            'role_id' => $cashierRole->id,
            'branch_id' => $branch->id,
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $this->category = ProductCategory::first() ?? ProductCategory::create([
            'name' => 'Kategori Test Bar',
            'description' => 'Untuk unit testing',
        ]);
    }

    public function test_cashier_is_forbidden_from_accessing_product_management(): void
    {
        $response = $this->actingAs($this->cashier)->get(route('products.index'));
        $response->assertStatus(403);
    }

    public function test_super_admin_can_access_product_management_index(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('products.index'));
        $response->assertStatus(200);
        $response->assertSee('Katalog Produk, Resep & Varian');
    }

    public function test_store_manager_can_access_product_management_index(): void
    {
        $response = $this->actingAs($this->storeManager)->get(route('products.index'));
        $response->assertStatus(200);
    }

    public function test_head_roaster_can_access_product_management_index(): void
    {
        $response = $this->actingAs($this->headRoaster)->get(route('products.index'));
        $response->assertStatus(200);
    }

    public function test_super_admin_can_create_new_product(): void
    {
        $sku = 'PRD-TEST-'.uniqid();
        $response = $this->actingAs($this->superAdmin)->post(route('products.store'), [
            'name' => 'Caramel Macchiato Velvet',
            'sku' => $sku,
            'category_id' => $this->category->id,
            'unit' => 'cup',
            'selling_price' => 45000,
            'cost_price' => 12000,
            'is_raw_material' => 0,
            'description' => 'Espresso dengan saus karamel dan susu lembut.',
        ]);

        $response->assertRedirect(route('products.index', ['tab' => 'products']));
        $this->assertDatabaseHas('products', [
            'sku' => $sku,
            'name' => 'Caramel Macchiato Velvet',
            'selling_price' => 45000,
        ]);
    }

    public function test_cashier_cannot_create_product(): void
    {
        $response = $this->actingAs($this->cashier)->post(route('products.store'), [
            'name' => 'Unauthorized Drink',
            'sku' => 'PRD-UNAUTH-'.uniqid(),
            'category_id' => $this->category->id,
            'unit' => 'cup',
            'selling_price' => 20000,
        ]);

        $response->assertStatus(403);
    }

    public function test_product_recipe_can_be_stored_and_calculates_cost(): void
    {
        $rawBean = Product::where('is_raw_material', true)->first();
        $beverage = Product::where('is_raw_material', false)->first();

        $this->assertNotNull($rawBean);
        $this->assertNotNull($beverage);

        $response = $this->actingAs($this->headRoaster)->post(route('products.recipes.store', $beverage), [
            'ingredient_product_id' => $rawBean->id,
            'quantity_required' => 20.00,
            'unit' => 'gram',
        ]);

        $response->assertRedirect(route('products.index', ['tab' => 'recipes']));
        $this->assertDatabaseHas('product_recipes', [
            'product_id' => $beverage->id,
            'ingredient_product_id' => $rawBean->id,
            'quantity_required' => 20.00,
        ]);

        // Verifikasi kalkulasi estimasi HPP resep
        $calculatedCost = $beverage->calculateRecipeCost();
        $this->assertGreaterThan(0, $calculatedCost);
    }

    public function test_store_manager_can_create_variant_and_modifier(): void
    {
        $beverage = Product::where('is_raw_material', false)->first();
        $this->assertNotNull($beverage);

        // Tambah Varian
        $response = $this->actingAs($this->storeManager)->post(route('products.variants.store', $beverage), [
            'name' => 'Grande 16oz',
            'sku' => $beverage->sku.'-GRANDE',
            'price_adjustment' => 8000,
        ]);

        $response->assertRedirect(route('products.index', ['tab' => 'variants']));
        $this->assertDatabaseHas('product_variants', [
            'product_id' => $beverage->id,
            'name' => 'Grande 16oz',
            'price_adjustment' => 8000,
        ]);

        // Tambah Modifier
        $modResponse = $this->actingAs($this->storeManager)->post(route('products.modifiers.store'), [
            'name' => 'Caramel Cold Foam',
            'group_name' => 'Topping & Float',
            'price' => 9000,
            'ingredient_product_id' => null,
            'category_id' => $this->category->id,
        ]);

        $modResponse->assertRedirect(route('products.index', ['tab' => 'variants']));
        $this->assertDatabaseHas('product_modifiers', [
            'name' => 'Caramel Cold Foam',
            'price' => 9000,
        ]);
    }

    public function test_toggle_product_active_status(): void
    {
        $product = Product::first();
        $this->assertNotNull($product);

        $initialStatus = $product->is_active;

        $response = $this->actingAs($this->superAdmin)->post(route('products.toggle', $product));
        $response->assertRedirect();

        $product->refresh();
        $this->assertEquals(! $initialStatus, $product->is_active);
    }
}
