<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperOrder
 */
/**
 * Header đơn hàng bán ra.
 *
 * `Order` giữ phần header như tổng tiền, trạng thái thanh toán,
 * và liên kết tới customer hoặc warehouse.
 * Chi tiết hàng hóa được tách sang `order_items`.
 */
class Order extends Model
{
    protected $fillable = [
        'business_id',
        'warehouse_id',
        'customer_id',
        'created_by',
        'order_no',
        'order_date',
        'status',
        'payment_status',
        'subtotal',
        'discount_amount',
        'shipping_amount',
        'total_amount',
        'paid_amount',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'shipping_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
        ];
    }

    public function business(): BelongsTo
    {
        // Xác định tenant sở hữu đơn hàng này.
        return $this->belongsTo(Business::class);
    }

    public function warehouse(): BelongsTo
    {
        // Kho dự kiến dùng để xuất hàng cho đơn.
        return $this->belongsTo(Warehouse::class);
    }

    public function customer(): BelongsTo
    {
        // Khách mua của đơn hàng.
        return $this->belongsTo(Customer::class);
    }

    public function creator(): BelongsTo
    {
        // User tạo đơn hàng.
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        // Các dòng hàng của đơn.
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        // Các phiếu thu liên kết với đơn này.
        return $this->hasMany(Payment::class);
    }

    public function stockOuts(): HasMany
    {
        // Các chứng từ xuất kho được tạo để giao đơn này.
        return $this->hasMany(StockOut::class);
    }
}
