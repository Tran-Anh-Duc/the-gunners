<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperOrderItem
 */
/**
 * Dong chỉ tiết đơn hàng.
 *
 * Luu snapshot SKU/tên/giá để đơn cu không bị anh hướng khi catalog thay doi.
 */
class OrderItem extends Model
{
    protected $fillable = [
        'business_id',
        'order_id',
        'product_id',
        'product_sku',
        'product_name',
        'quantity',
        'unit_price',
        'discount_amount',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function business(): BelongsTo
    {
        // Dong item thuộc business nào.
        return $this->belongsTo(Business::class);
    }

    public function order(): BelongsTo
    {
        // Dong item nay thuoc đơn nào.
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        // Sản phẩm goc của dòng item.
        return $this->belongsTo(Product::class);
    }
}
