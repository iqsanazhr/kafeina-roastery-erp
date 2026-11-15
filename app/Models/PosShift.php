<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosShift extends Model
{
    use HasFactory;

    protected $fillable = [
        'shift_code',
        'cashier_user_id',
        'branch_id',
        'opening_cash',
        'closing_cash_actual',
        'closing_cash_system',
        'cash_difference',
        'opened_at',
        'closed_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'opening_cash' => 'decimal:2',
            'closing_cash_actual' => 'decimal:2',
            'closing_cash_system' => 'decimal:2',
            'cash_difference' => 'decimal:2',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_user_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(PosOrder::class, 'shift_id');
    }

    public function hasDiscrepancy(): bool
    {
        if ($this->cash_difference === null) {
            return false;
        }

        return abs((float) $this->cash_difference) > 10000.00;
    }

    public function isOpen(): bool
    {
        return $this->status === 'OPEN';
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'OPEN');
    }

    public function scopeClosed(Builder $query): Builder
    {
        return $query->where('status', 'CLOSED');
    }

    public function scopeForBranch(Builder $query, int $branchId): Builder
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeForCashier(Builder $query, int $userId): Builder
    {
        return $query->where('cashier_user_id', $userId);
    }
}
