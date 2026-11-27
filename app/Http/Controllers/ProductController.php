<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductModifier;
use App\Models\ProductRecipe;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    /**
     * Tampilkan halaman utama manajemen katalog produk, resep, dan varian.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $categoryId = $request->input('category_id');
        $type = $request->input('type', 'all'); // all, beverage, retail, raw, packaging
        $status = $request->input('status', 'all'); // all, active, inactive
        $activeTab = $request->input('tab', 'products'); // products, recipes, variants, categories

        $query = Product::with([
            'category',
            'recipes.ingredientProduct',
            'boms.items.materialProduct',
            'variants',
            'modifiers',
        ]);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('sku', 'LIKE', "%{$search}%");
            });
        }

        if ($categoryId && $categoryId !== 'all') {
            $query->where('category_id', $categoryId);
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        if ($type === 'raw') {
            $query->where('is_raw_material', true);
        } elseif ($type === 'beverage') {
            $query->where('is_raw_material', false)
                ->where('unit', 'cup');
        } elseif ($type === 'retail') {
            $query->where('is_raw_material', false)
                ->whereIn('unit', ['pouch', 'pcs', 'pack']);
        }

        $products = $query->orderBy('is_raw_material', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        // Data pendukung
        $categories = ProductCategory::withCount('products')->orderBy('name')->get();
        $rawMaterials = Product::where('is_raw_material', true)->orderBy('name')->get();
        $modifiers = ProductModifier::with(['ingredientProduct', 'category'])->orderBy('group_name')->get();
        $allVariants = ProductVariant::with('product')->orderBy('name')->get();

        // Ringkasan Statistik
        $totalProducts = Product::count();
        $activeBeverages = Product::where('is_raw_material', false)->where('is_active', true)->count();
        $rawCount = Product::where('is_raw_material', true)->count();
        $recipesCount = ProductRecipe::distinct('product_id')->count('product_id');

        return view('products.index', [
            'products' => $products,
            'categories' => $categories,
            'rawMaterials' => $rawMaterials,
            'modifiers' => $modifiers,
            'variants' => $allVariants,
            'activeTab' => $activeTab,
            'filters' => [
                'search' => $search,
                'category_id' => $categoryId,
                'type' => $type,
                'status' => $status,
            ],
            'stats' => [
                'total_products' => $totalProducts,
                'active_beverages' => $activeBeverages,
                'raw_count' => $rawCount,
                'recipes_count' => $recipesCount,
            ],
        ]);
    }

    /**
     * Simpan produk baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:product_categories,id',
            'sku' => 'required|string|max:50|unique:products,sku',
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:20',
            'selling_price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'is_raw_material' => 'nullable|boolean',
            'description' => 'nullable|string',
            'image' => 'nullable|string',
            'temperatures' => 'nullable|array',
        ]);

        $validated['is_raw_material'] = (bool) ($validated['is_raw_material'] ?? false);
        $validated['is_active'] = true;
        $validated['cost_price'] = $validated['cost_price'] ?? 0;

        Product::create($validated);

        return redirect()->route('products.index', ['tab' => 'products'])
            ->with('success', 'Produk berhasil ditambahkan ke katalog.');
    }

    /**
     * Perbarui data produk.
     */
    public function update(Request $request, Product $product): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'category_id' => 'required|exists:product_categories,id',
            'sku' => 'required|string|max:50|unique:products,sku,'.$product->id,
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:20',
            'selling_price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'is_raw_material' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string',
            'image' => 'nullable|string',
            'temperatures' => 'nullable|array',
        ]);

        // Cek izin penetapan harga jika harga diubah
        if (! $user->canManagePricing() && ((float) $validated['selling_price'] !== (float) $product->selling_price)) {
            return redirect()->back()->with('error', 'Anda tidak memiliki wewenang untuk mengubah harga jual produk.');
        }

        $validated['is_raw_material'] = (bool) ($validated['is_raw_material'] ?? $product->is_raw_material);
        $validated['is_active'] = (bool) ($validated['is_active'] ?? $product->is_active);

        $product->update($validated);

        return redirect()->route('products.index', ['tab' => 'products'])
            ->with('success', "Produk [{$product->name}] berhasil diperbarui.");
    }

    /**
     * Toggle status aktif produk.
     */
    public function toggleActive(Product $product): RedirectResponse
    {
        $product->is_active = ! $product->is_active;
        $product->save();

        $statusText = $product->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->back()
            ->with('success', "Status produk [{$product->name}] berhasil {$statusText}.");
    }

    /**
     * Tambah bahan baku ke formula resep produk.
     */
    public function storeRecipe(Request $request, Product $product): RedirectResponse
    {
        $user = $request->user();
        if (! $user->canManageRecipes()) {
            return redirect()->back()->with('error', 'Anda tidak memiliki wewenang untuk memodifikasi formula resep.');
        }

        $validated = $request->validate([
            'ingredient_product_id' => 'required|exists:products,id',
            'quantity_required' => 'required|numeric|gt:0',
            'unit' => 'required|string|max:20',
        ]);

        // Hindari dependensi sirkular (produk menjadi bahan untuk dirinya sendiri)
        if ((int) $validated['ingredient_product_id'] === $product->id) {
            return redirect()->back()->with('error', 'Produk tidak dapat menggunakan dirinya sendiri sebagai bahan baku.');
        }

        ProductRecipe::updateOrCreate(
            [
                'product_id' => $product->id,
                'ingredient_product_id' => $validated['ingredient_product_id'],
            ],
            [
                'quantity_required' => $validated['quantity_required'],
                'unit' => $validated['unit'],
            ]
        );

        return redirect()->route('products.index', ['tab' => 'recipes'])
            ->with('success', "Formula resep untuk [{$product->name}] berhasil disimpan.");
    }

    /**
     * Hapus bahan baku dari resep produk.
     */
    public function destroyRecipe(Request $request, ProductRecipe $recipe): RedirectResponse
    {
        $user = $request->user();
        if (! $user->canManageRecipes()) {
            return redirect()->back()->with('error', 'Anda tidak memiliki wewenang untuk menghapus formula resep.');
        }

        $recipe->delete();

        return redirect()->route('products.index', ['tab' => 'recipes'])
            ->with('success', 'Bahan baku berhasil dihapus dari resep.');
    }

    /**
     * Tambah varian ukuran / kemasan produk.
     */
    public function storeVariant(Request $request, Product $product): RedirectResponse
    {
        $user = $request->user();
        if (! $user->canManagePricing()) {
            return redirect()->back()->with('error', 'Anda tidak memiliki wewenang untuk menambah varian harga.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'sku' => 'nullable|string|max:50|unique:product_variants,sku',
            'price_adjustment' => 'required|numeric',
        ]);

        $product->variants()->create([
            'name' => $validated['name'],
            'sku' => $validated['sku'] ?: $product->sku.'-'.strtoupper(substr(md5(uniqid()), 0, 4)),
            'price_adjustment' => $validated['price_adjustment'],
            'is_active' => true,
        ]);

        return redirect()->route('products.index', ['tab' => 'variants'])
            ->with('success', "Varian [{$validated['name']}] untuk [{$product->name}] berhasil ditambahkan.");
    }

    /**
     * Hapus varian produk.
     */
    public function destroyVariant(Request $request, ProductVariant $variant): RedirectResponse
    {
        $user = $request->user();
        if (! $user->canManagePricing()) {
            return redirect()->back()->with('error', 'Anda tidak memiliki wewenang untuk menghapus varian.');
        }

        $variant->delete();

        return redirect()->route('products.index', ['tab' => 'variants'])
            ->with('success', 'Varian produk berhasil dihapus.');
    }

    /**
     * Simpan modifier / add-on baru.
     */
    public function storeModifier(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user->canManagePricing()) {
            return redirect()->back()->with('error', 'Anda tidak memiliki wewenang untuk menambah modifier harga.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'group_name' => 'required|string|max:50',
            'price' => 'required|numeric|min:0',
            'ingredient_product_id' => 'nullable|exists:products,id',
            'quantity_deducted' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:20',
            'category_id' => 'nullable|exists:product_categories,id',
        ]);

        $validated['is_active'] = true;

        ProductModifier::create($validated);

        return redirect()->route('products.index', ['tab' => 'variants'])
            ->with('success', "Modifier [{$validated['name']}] berhasil ditambahkan.");
    }

    /**
     * Hapus modifier.
     */
    public function destroyModifier(Request $request, ProductModifier $modifier): RedirectResponse
    {
        $user = $request->user();
        if (! $user->canManagePricing()) {
            return redirect()->back()->with('error', 'Anda tidak memiliki wewenang untuk menghapus modifier.');
        }

        $modifier->delete();

        return redirect()->route('products.index', ['tab' => 'variants'])
            ->with('success', 'Modifier berhasil dihapus.');
    }

    /**
     * Simpan kategori produk baru.
     */
    public function storeCategory(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:product_categories,name',
            'description' => 'nullable|string',
        ]);

        ProductCategory::create($validated);

        return redirect()->route('products.index', ['tab' => 'categories'])
            ->with('success', "Kategori [{$validated['name']}] berhasil dibuat.");
    }

    /**
     * Perbarui kategori produk.
     */
    public function updateCategory(Request $request, ProductCategory $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:product_categories,name,'.$category->id,
            'description' => 'nullable|string',
        ]);

        $category->update($validated);

        return redirect()->route('products.index', ['tab' => 'categories'])
            ->with('success', "Kategori [{$category->name}] berhasil diperbarui.");
    }
}
