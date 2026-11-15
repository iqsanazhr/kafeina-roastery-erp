<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'shift_id',
        'cashier_id',
        'branch_id',
        'order_type',
        'customer_name',
        'table_number',
        'customer_phone',
        'notes',
        'non_revenue_reason',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'payment_method',
        'payment_status',
        'payment_reference',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(PosShift::class, 'shift_id');
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PosOrderItem::class, 'pos_order_id');
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('payment_status', 'PAID');
    }

    public function scopeNonRevenue(Builder $query): Builder
    {
        return $query->where('order_type', 'NON_REVENUE');
    }

    public function scopeForBranch(Builder $query, int $branchId): Builder
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeForShift(Builder $query, int $shiftId): Builder
    {
        return $query->where('shift_id', $shiftId);
    }

    public function scopeDateRange(Builder $query, ?string $startDate, ?string $endDate): Builder
    {
        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        return $query;
    }

    public function scopePaymentMethod(Builder $query, ?string $method): Builder
    {
        if ($method && $method !== 'ALL') {
            $query->where('payment_method', $method);
        }

        return $query;
    }

    public function scopeOrderType(Builder $query, ?string $type): Builder
    {
        if ($type && $type !== 'ALL') {
            $query->where('order_type', $type);
        }

        return $query;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if ($term && trim($term) !== '') {
            $trimmed = trim($term);
            $query->where(function (Builder $q) use ($trimmed) {
                $q->where('order_number', 'LIKE', "%{$trimmed}%")
                    ->orWhere('customer_name', 'LIKE', "%{$trimmed}%")
                    ->orWhere('table_number', 'LIKE', "%{$trimmed}%")
                    ->orWhere('payment_reference', 'LIKE', "%{$trimmed}%");
            });
        }

        return $query;
    }
}
