<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperStockOutItem
 */
/**
 * Dong chỉ tiết phiếu xuất kho.
 *
 * unit_price là giá ban/sales amount; giá von se do ledger tinh lại khi confirm.
 */
class StockOutItem extends Model
{
    protected $fillable = [
        'business_id',
        'stock_out_id',
        'product_id',
        'product_sku',
        'product_name',
        'quantity',
        'unit_price',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function business(): BelongsTo
    {
        // Dong xuat hang thuộc business nào.
        return $this->belongsTo(Business::class);
    }

    public function stockOut(): BelongsTo
    {
        // Dong nay nằm trong phiếu xuat nào.
        return $this->belongsTo(StockOut::class);
    }

    public function product(): BelongsTo
    {
        // Sản phẩm được xuat.
        return $this->belongsTo(Product::class);
    }
}
