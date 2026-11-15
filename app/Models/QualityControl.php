<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualityControl extends Model
{
    use HasFactory;

    protected $fillable = [
        'lot_id',
        'inspector_id',
        'inspection_date',
        'moisture_content',
        'bulk_density_g_l',
        'primary_defects',
        'secondary_defects',
        'sca_cupping_score',
        'qc_status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'inspection_date' => 'date',
            'moisture_content' => 'decimal:2',
            'bulk_density_g_l' => 'decimal:2',
            'primary_defects' => 'integer',
            'secondary_defects' => 'integer',
            'sca_cupping_score' => 'decimal:2',
        ];
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(GreenBeanLot::class, 'lot_id');
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    public function isSpecialty(): bool
    {
        $moisture = (float) $this->moisture_content;
        $primaryDefects = (int) $this->primary_defects;
        $sca = (float) $this->sca_cupping_score;

        return $moisture >= 10.00 && $moisture <= 12.00 && $primaryDefects === 0 && $sca >= 80.00;
    }

    public function scopeSpecialtyGrade(Builder $query): Builder
    {
        return $query->whereBetween('moisture_content', [10.00, 12.00])
            ->where('primary_defects', 0)
            ->where('sca_cupping_score', '>=', 80.00);
    }

    public function scopeAccepted(Builder $query): Builder
    {
        return $query->where('qc_status', 'ACCEPTED');
    }
}
