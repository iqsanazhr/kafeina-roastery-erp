<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'sku',
        'name',
        'description',
        'image',
        'unit',
        'cost_price',
        'selling_price',
        'temperatures',
        'is_raw_material',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'temperatures' => 'array',
            'is_raw_material' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class, 'product_id');
    }

    public function modifiers(): HasMany
    {
        return $this->hasMany(ProductModifier::class, 'product_id');
    }

    public function recipes(): HasMany
    {
        return $this->hasMany(ProductRecipe::class, 'product_id');
    }

    /**
     * Hitung estimasi HPP resep berbasis bahan baku riil.
     */
    public function calculateRecipeCost(): float
    {
        if ($this->is_raw_material) {
            return (float) $this->cost_price;
        }

        $totalCost = 0.0;
        foreach ($this->recipes as $recipe) {
            $ingredientCost = (float) ($recipe->ingredientProduct?->cost_price ?? 0);
            $totalCost += $ingredientCost * (float) $recipe->quantity_required;
        }

        return $totalCost > 0 ? $totalCost : (float) $this->cost_price;
    }

    /**
     * Hitung Gross Margin persen: ((Selling Price - HPP) / Selling Price) * 100
     */
    public function calculateGrossMargin(): float
    {
        $sellingPrice = (float) $this->selling_price;
        if ($sellingPrice <= 0) {
            return 0.0;
        }

        $hpp = $this->calculateRecipeCost();

        return round((($sellingPrice - $hpp) / $sellingPrice) * 100, 1);
    }

    public function usedInRecipes(): HasMany
    {
        return $this->hasMany(ProductRecipe::class, 'ingredient_product_id');
    }

    public function boms(): HasMany
    {
        return $this->hasMany(Bom::class, 'product_id');
    }

    public function usedInBoms(): HasMany
    {
        return $this->hasMany(BomItem::class, 'material_product_id');
    }

    public function warehouseStocks(): HasMany
    {
        return $this->hasMany(WarehouseStock::class, 'product_id');
    }

    public function stockCards(): HasMany
    {
        return $this->hasMany(StockCard::class, 'product_id');
    }

    public function materialRequestItems(): HasMany
    {
        return $this->hasMany(MaterialRequestItem::class, 'product_id');
    }

    public function deliveryNoteItems(): HasMany
    {
        return $this->hasMany(DeliveryNoteItem::class, 'product_id');
    }

    public function posOrderItems(): HasMany
    {
        return $this->hasMany(PosOrderItem::class, 'product_id');
    }

    public function recipeDeductions(): HasMany
    {
        return $this->hasMany(RecipeDeduction::class, 'ingredient_product_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeRawMaterials(Builder $query): Builder
    {
        return $query->where('is_raw_material', true);
    }

    public function scopeFinishedGoods(Builder $query): Builder
    {
        return $query->where('is_raw_material', false);
    }

    public function scopeByCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }
}
