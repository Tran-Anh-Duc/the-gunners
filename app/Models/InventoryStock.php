<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryStock extends Model
{
    protected $fillable = [
        'business_id',
        'warehouse_id',
        'product_id',
        'quantity_on_hand',
        'avg_unit_cost',
        'inventory_value',
        'last_movement_id',
        'last_movement_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity_on_hand' => 'decimal:2',
            'avg_unit_cost' => 'decimal:4',
            'inventory_value' => 'decimal:4',
            'last_movement_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function lastMovement(): BelongsTo
    {
        return $this->belongsTo(InventoryStockMovement::class, 'last_movement_id');
    }
}
