<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperStockIn
 */
/**
 * Header phiếu nhập kho.
 *
 * Document nay là đầu vao chinh của tồn kho va giá von.
 * Chi tiết hang nhap được luu o stock_in_items.
 */
class StockIn extends Model
{
    protected $table = 'stock_in';

    protected $fillable = [
        'business_id',
        'warehouse_id',
        'supplier_id',
        'created_by',
        'stock_in_no',
        'reference_no',
        'stock_in_type',
        'stock_in_date',
        'status',
        'subtotal',
        'discount_amount',
        'total_amount',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'stock_in_date' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function business(): BelongsTo
    {
        // Phieu nhap thuộc business nào.
        return $this->belongsTo(Business::class);
    }

    public function warehouse(): BelongsTo
    {
        // Kho nhận hang.
        return $this->belongsTo(Warehouse::class);
    }

    public function supplier(): BelongsTo
    {
        // Nhà cùng cấp của phiếu nhap nếu co.
        return $this->belongsTo(Supplier::class);
    }

    public function creator(): BelongsTo
    {
        // User lap phiếu nhap.
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        // Cac dòng hang được nhap.
        return $this->hasMany(StockInItem::class);
    }

    public function payments(): HasMany
    {
        // Cac payment chỉ tien liên quan đến phiếu nhap nay.
        return $this->hasMany(Payment::class);
    }
}
