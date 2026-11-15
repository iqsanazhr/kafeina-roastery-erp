<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'wo_number',
        'lot_id',
        'profile_id',
        'roaster_id',
        'green_weight_grams',
        'roasted_weight_grams',
        'yield_loss_percentage',
        'status',
        'roast_start_time',
        'roast_end_time',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'green_weight_grams' => 'decimal:2',
            'roasted_weight_grams' => 'decimal:2',
            'yield_loss_percentage' => 'decimal:2',
            'roast_start_time' => 'datetime',
            'roast_end_time' => 'datetime',
        ];
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(GreenBeanLot::class, 'lot_id');
    }

    public function roastingProfile(): BelongsTo
    {
        return $this->belongsTo(RoastingProfile::class, 'profile_id');
    }

    public function roaster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'roaster_id');
    }

    public function isYieldNormal(): bool
    {
        if ($this->yield_loss_percentage === null) {
            return false;
        }

        $yield = (float) $this->yield_loss_percentage;

        return $yield >= 14.00 && $yield <= 18.00;
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'COMPLETED');
    }

    public function scopeYieldAnomaly(Builder $query): Builder
    {
        return $query->where('status', 'COMPLETED')
            ->where(function (Builder $q) {
                $q->where('yield_loss_percentage', '<', 14.00)
                    ->orWhere('yield_loss_percentage', '>', 18.00);
            });
    }
}
