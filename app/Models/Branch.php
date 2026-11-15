<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type',
        'address',
        'phone',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'branch_id');
    }

    public function warehouses(): HasMany
    {
        return $this->hasMany(InventoryWarehouse::class, 'branch_id');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'branch_id');
    }

    public function outgoingMaterialRequests(): HasMany
    {
        return $this->hasMany(MaterialRequest::class, 'from_branch_id');
    }

    public function incomingMaterialRequests(): HasMany
    {
        return $this->hasMany(MaterialRequest::class, 'to_branch_id');
    }

    public function posShifts(): HasMany
    {
        return $this->hasMany(PosShift::class, 'branch_id');
    }

    public function posOrders(): HasMany
    {
        return $this->hasMany(PosOrder::class, 'branch_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeRoasteries(Builder $query): Builder
    {
        return $query->where('type', 'CENTRAL_ROASTERY');
    }

    public function scopeOutlets(Builder $query): Builder
    {
        return $query->where('type', 'RETAIL_BRANCH');
    }
}
