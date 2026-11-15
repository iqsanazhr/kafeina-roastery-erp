<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoastingProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'profile_code',
        'name',
        'roast_level',
        'charge_temp_celsius',
        'turning_point_celsius',
        'development_time_ratio',
        'target_air_flow',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'charge_temp_celsius' => 'decimal:2',
            'turning_point_celsius' => 'decimal:2',
            'development_time_ratio' => 'decimal:2',
        ];
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class, 'profile_id');
    }

    public function scopeLevel(Builder $query, string $level): Builder
    {
        return $query->where('roast_level', strtoupper($level));
    }
}
