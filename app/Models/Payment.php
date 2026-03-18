<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperPayment
 */
/**
 * Phiếu thu/chi.
 *
 * Ở giai đoạn MVP, payment có thể liên kết tới `order` hoặc `stock_in`
 * để theo dõi cả thu tiền bán hàng lẫn chi tiền nhập hàng cơ bản.
 */
class Payment extends Model
{
    protected $fillable = [
        'business_id',
        'order_id',
        'stock_in_id',
        'customer_id',
        'supplier_id',
        'created_by',
        'payment_no',
        'direction',
        'method',
        'status',
        'amount',
        'payment_date',
        'reference_no',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_date' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        // Xác định business sở hữu phiếu thu/chi này.
        return $this->belongsTo(Business::class);
    }

    public function order(): BelongsTo
    {
        // Nếu là thu tiền đơn hàng, payment sẽ trỏ vào order này.
        return $this->belongsTo(Order::class);
    }

    public function stockIn(): BelongsTo
    {
        // Nếu là chi tiền nhập hàng, payment có thể trỏ vào phiếu nhập kho.
        return $this->belongsTo(StockIn::class);
    }

    public function customer(): BelongsTo
    {
        // Khách hàng liên quan đến payment có hướng `in`.
        return $this->belongsTo(Customer::class);
    }

    public function supplier(): BelongsTo
    {
        // Nhà cung cấp liên quan đến payment có hướng `out`.
        return $this->belongsTo(Supplier::class);
    }

    public function creator(): BelongsTo
    {
        // User tạo phiếu thu/chi.
        return $this->belongsTo(User::class, 'created_by');
    }
}
