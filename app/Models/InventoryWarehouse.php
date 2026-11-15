<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryWarehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'code',
        'name',
        'type',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(WarehouseStock::class, 'warehouse_id');
    }

    public function stockCards(): HasMany
    {
        return $this->hasMany(StockCard::class, 'warehouse_id');
    }

    public function dispatchedDeliveryNotes(): HasMany
    {
        return $this->hasMany(DeliveryNote::class, 'source_warehouse_id');
    }

    public function receivedDeliveryNotes(): HasMany
    {
        return $this->hasMany(DeliveryNote::class, 'dest_warehouse_id');
    }

    public function recipeDeductions(): HasMany
    {
        return $this->hasMany(RecipeDeduction::class, 'warehouse_id');
    }
}
