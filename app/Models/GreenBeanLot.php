<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GreenBeanLot extends Model
{
    use HasFactory;

    protected $fillable = [
        'lot_number',
        'purchase_order_id',
        'variety',
        'origin_region',
        'process_type',
        'harvest_year',
        'initial_weight_grams',
        'current_weight_grams',
        'price_per_gram',
    ];

    protected function casts(): array
    {
        return [
            'harvest_year' => 'integer',
            'initial_weight_grams' => 'decimal:2',
            'current_weight_grams' => 'decimal:2',
            'price_per_gram' => 'decimal:4',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function qualityControl(): HasOne
    {
        return $this->hasOne(QualityControl::class, 'lot_id');
    }

    public function qualityControls(): HasMany
    {
        return $this->hasMany(QualityControl::class, 'lot_id');
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class, 'lot_id');
    }

    public function stockCards(): HasMany
    {
        return $this->hasMany(StockCard::class, 'lot_id');
    }

    public function deliveryNoteItems(): HasMany
    {
        return $this->hasMany(DeliveryNoteItem::class, 'lot_id');
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('current_weight_grams', '>', 0);
    }

    public function scopeByVariety(Builder $query, string $variety): Builder
    {
        return $query->where('variety', $variety);
    }

    public static function getAvailableStock(int $lotId): float
    {
        $lot = static::find($lotId);

        return $lot ? (float) $lot->current_weight_grams : 0.0;
    }
}
