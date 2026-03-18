<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bang tổng hợp tồn kho hiện tại.
 *
 * current_stocks chỉ là read model để query nhanh.
 * Nguon su that van là inventory_movements.
 */
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
        // Dong tồn kho nay thuộc business nào.
        return $this->belongsTo(Business::class);
    }

    public function warehouse(): BelongsTo
    {
        // Tồn kho hiện tại của kho nào.
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        // Tồn kho hiện tại của sản phẩm nào.
        return $this->belongsTo(Product::class);
    }
}
