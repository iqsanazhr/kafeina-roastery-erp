<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecipeDeduction extends Model
{
    use HasFactory;

    protected $fillable = [
        'pos_order_item_id',
        'ingredient_product_id',
        'warehouse_id',
        'quantity_deducted',
        'unit',
        'deduction_timestamp',
    ];

    protected function casts(): array
    {
        return [
            'quantity_deducted' => 'decimal:4',
            'deduction_timestamp' => 'datetime',
        ];
    }

    public function posOrderItem(): BelongsTo
    {
        return $this->belongsTo(PosOrderItem::class, 'pos_order_item_id');
    }

    public function ingredientProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'ingredient_product_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(InventoryWarehouse::class, 'warehouse_id');
    }
}
