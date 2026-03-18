<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperStockInItem
 */
/**
 * Dong chỉ tiết phiếu nhập kho.
 *
 * Luu snapshot sản phẩm va giá nhap tai thoi diem phat sinh.
 */
class StockInItem extends Model
{
    protected $fillable = [
        'business_id',
        'stock_in_id',
        'product_id',
        'product_sku',
        'product_name',
        'quantity',
        'unit_cost',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_cost' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function business(): BelongsTo
    {
        // Dong nhập hàng thuộc business nào.
        return $this->belongsTo(Business::class);
    }

    public function stockIn(): BelongsTo
    {
        // Dong nay nằm trong phiếu nhap nào.
        return $this->belongsTo(StockIn::class);
    }

    public function product(): BelongsTo
    {
        // Sản phẩm được nhap.
        return $this->belongsTo(Product::class);
    }
}
