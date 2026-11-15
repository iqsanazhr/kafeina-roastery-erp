<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'product_id',
        'lot_id',
        'reference_type',
        'reference_id',
        'quantity_in',
        'quantity_out',
        'balance_after',
        'cost_per_unit',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'reference_id' => 'integer',
            'quantity_in' => 'decimal:4',
            'quantity_out' => 'decimal:4',
            'balance_after' => 'decimal:4',
            'cost_per_unit' => 'decimal:2',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(InventoryWarehouse::class, 'warehouse_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(GreenBeanLot::class, 'lot_id');
    }
}
