<?php

namespace App\Models;

use App\Support\BusinessSequenceGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperWarehouse
 */
/**
 * Kho của business.
 *
 * Warehouse là chiều dữ liệu rất quan trọng:
 * - stock_in
 * - stock_out
 * - stock_adjustment
 * - inventory_movements
 * - current_stocks
 */
class Warehouse extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'business_id',
        'code',
        'name',
        'address',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $warehouse): void {
            if (! empty($warehouse->code) || empty($warehouse->business_id)) {
                return;
            }

            $warehouse->code = BusinessSequenceGenerator::nextFormatted(self::class, (int) $warehouse->business_id, 'code', 'WH');
        });
    }

    public function business(): BelongsTo
    {
        // Kho này thuộc business nào.
        return $this->belongsTo(Business::class);
    }

    public function orders(): HasMany
    {
        // Các đơn hàng được gắn kho xử lý này.
        return $this->hasMany(Order::class);
    }

    public function stockIns(): HasMany
    {
        // Các phiếu nhập vào kho này.
        return $this->hasMany(StockIn::class);
    }

    public function stockOuts(): HasMany
    {
        // Các phiếu xuất từ kho này.
        return $this->hasMany(StockOut::class);
    }

    public function stockAdjustments(): HasMany
    {
        // Các chứng từ kiểm kho/điều chỉnh tồn tại kho này.
        return $this->hasMany(StockAdjustment::class);
    }

    public function inventoryMovements(): HasMany
    {
        // Lịch sử biến động tồn kho xảy ra tại kho này.
        return $this->hasMany(InventoryMovement::class);
    }

    public function currentStocks(): HasMany
    {
        // Tồn hiện tại của tất cả sản phẩm trong kho này.
        return $this->hasMany(CurrentStock::class);
    }

    public function inventories(): HasMany
    {
        // Alias tương thích ngược cho current stocks.
        return $this->hasMany(Inventory::class);
    }
}
