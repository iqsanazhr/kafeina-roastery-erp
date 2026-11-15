<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_number',
        'request_id',
        'source_warehouse_id',
        'dest_warehouse_id',
        'dispatched_by_user_id',
        'courier_name',
        'tracking_code',
        'status',
        'notes',
    ];

    public function materialRequest(): BelongsTo
    {
        return $this->belongsTo(MaterialRequest::class, 'request_id');
    }

    public function sourceWarehouse(): BelongsTo
    {
        return $this->belongsTo(InventoryWarehouse::class, 'source_warehouse_id');
    }

    public function destWarehouse(): BelongsTo
    {
        return $this->belongsTo(InventoryWarehouse::class, 'dest_warehouse_id');
    }

    public function dispatchedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispatched_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryNoteItem::class, 'delivery_note_id');
    }
}
