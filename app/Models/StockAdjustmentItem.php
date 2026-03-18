<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Chi tiết adjustment.
 *
 * Gom 3 giá tri chinh:
 * - expected_qty: ton hệ thống
 * - counted_qty: ton thực tế
 * - difference_qty: phan chênh lệch dua vao ledger
 */
class StockAdjustmentItem extends Model
{
    protected $fillable = [
        'business_id',
        'stock_adjustment_id',
        'product_id',
        'product_sku',
        'product_name',
        'expected_qty',
        'counted_qty',
        'difference_qty',
        'unit_cost',
        'line_total',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'expected_qty' => 'decimal:3',
            'counted_qty' => 'decimal:3',
            'difference_qty' => 'decimal:3',
            'unit_cost' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function business(): BelongsTo
    {
        // Dong adjustment thuộc business nào.
        return $this->belongsTo(Business::class);
    }

    public function stockAdjustment(): BelongsTo
    {
        // Dong nay nằm trong chứng từ adjustment nào.
        return $this->belongsTo(StockAdjustment::class);
    }

    public function product(): BelongsTo
    {
        // Sản phẩm được kiểm kho/điều chỉnh.
        return $this->belongsTo(Product::class);
    }
}
