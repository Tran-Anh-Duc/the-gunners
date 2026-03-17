<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'business_id',
        'warehouse_id',
        'product_id',
        'movement_type',
        'source_type',
        'source_id',
        'source_code',
        'quantity_change',
        'unit_cost',
        'total_cost',
        'movement_date',
        'note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity_change' => 'decimal:3',
            'unit_cost' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'movement_date' => 'datetime',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
