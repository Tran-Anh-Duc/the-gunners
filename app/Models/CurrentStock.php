<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurrentStock extends Model
{
    protected $table = 'current_stocks';

    protected $fillable = [
        'business_id',
        'warehouse_id',
        'product_id',
        'quantity_on_hand',
        'avg_unit_cost',
        'stock_value',
        'last_movement_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity_on_hand' => 'decimal:3',
            'avg_unit_cost' => 'decimal:2',
            'stock_value' => 'decimal:2',
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
}
