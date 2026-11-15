<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'contact_person',
        'phone',
        'address',
        'farm_location',
        'altitude_masl',
        'rating_score',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'altitude_masl' => 'integer',
            'rating_score' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'supplier_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeHighAltitude(Builder $query, int $minAltitude = 1200): Builder
    {
        return $query->where('altitude_masl', '>=', $minAltitude);
    }
}
