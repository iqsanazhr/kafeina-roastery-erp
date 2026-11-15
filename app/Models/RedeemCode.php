<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RedeemCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'description',
        'discount_type',
        'discount_value',
        'min_order_amount',
        'max_discount_amount',
        'max_redemptions',
        'times_redeemed',
        'expires_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'min_order_amount' => 'decimal:2',
            'max_discount_amount' => 'decimal:2',
            'max_redemptions' => 'integer',
            'times_redeemed' => 'integer',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Check if redeem code is valid and has quota remaining.
     */
    public function validateForSubtotal(float $subtotal): array
    {
        if (! $this->is_active) {
            return ['valid' => false, 'message' => "Kode promo [{$this->code}] sedang tidak aktif."];
        }

        if ($this->expires_at && now()->greaterThan($this->expires_at)) {
            return ['valid' => false, 'message' => "Kode promo [{$this->code}] telah kedaluwarsa."];
        }

        if ($this->max_redemptions !== null && $this->times_redeemed >= $this->max_redemptions) {
            return ['valid' => false, 'message' => "Kuota penukaran kode promo [{$this->code}] telah habis (Maks: {$this->max_redemptions}x)."];
        }

        if ($subtotal < (float) $this->min_order_amount) {
            $minFormatted = number_format((float) $this->min_order_amount, 0, ',', '.');

            return ['valid' => false, 'message' => "Subtotal pesanan belum mencapai minimal belanja Rp {$minFormatted}."];
        }

        return ['valid' => true, 'message' => 'Kode promo berhasil digunakan.'];
    }

    /**
     * Calculate discount amount from subtotal.
     */
    public function calculateDiscount(float $subtotal): float
    {
        $discount = 0.0;

        if ($this->discount_type === 'PERCENTAGE') {
            $discount = $subtotal * ((float) $this->discount_value / 100);
            if ($this->max_discount_amount !== null && $discount > (float) $this->max_discount_amount) {
                $discount = (float) $this->max_discount_amount;
            }
        } else {
            $discount = (float) $this->discount_value;
        }

        return round(min($discount, $subtotal), 2);
    }
}
