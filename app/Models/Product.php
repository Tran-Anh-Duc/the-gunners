<?php

namespace App\Models;

use App\Traits\HasNameSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperProduct
 */
/**
 * Sản phẩm của business.
 *
 * Ở giai đoạn MVP, sản phẩm được giữ dưới dạng simple product, chưa tách variant.
 * Cách mô hình hóa này giúp bài toán bán hàng và tồn kho dễ triển khai hơn:
 * - một sản phẩm gắn với một đơn vị tính mặc định;
 * - tồn kho được theo dõi trực tiếp trên chính `product_id`;
 * - các chứng từ sẽ chụp snapshot tên, SKU và giá tại thời điểm phát sinh.
 */
class Product extends Model
{
    use HasNameSlug, SoftDeletes;

    protected $fillable = [
        'business_id',
        'unit_id',
        'category_id',
        'sku',
        'name',
        'name_slug',
        'barcode',
        'product_type',
        'track_inventory',
        'cost_price',
        'sale_price',
        'is_active',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'track_inventory' => 'boolean',
            'is_active' => 'boolean',
            'cost_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
        ];
    }

    public function business(): BelongsTo
    {
        // Xác định tenant sở hữu sản phẩm này.
        return $this->belongsTo(Business::class);
    }

    public function unit(): BelongsTo
    {
        // Đơn vị tính mặc định khi nhập, xuất hoặc bán sản phẩm.
        return $this->belongsTo(Unit::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function orderItems(): HasMany
    {
        // Các dòng bán hàng đã chụp snapshot từ sản phẩm này.
        return $this->hasMany(OrderItem::class);
    }

    public function stockInItems(): HasMany
    {
        // Các dòng nhập kho phát sinh cho sản phẩm này.
        return $this->hasMany(StockInItem::class);
    }

    public function stockOutItems(): HasMany
    {
        // Các dòng xuất kho phát sinh cho sản phẩm này.
        return $this->hasMany(StockOutItem::class);
    }

    public function stockAdjustmentItems(): HasMany
    {
        // Các dòng kiểm kho hoặc điều chỉnh tồn liên quan tới sản phẩm này.
        return $this->hasMany(StockAdjustmentItem::class);
    }

    public function inventoryMovements(): HasMany
    {
        // Toàn bộ lịch sử biến động tồn kho, là nguồn sự thật cho bài toán inventory.
        return $this->hasMany(InventoryMovement::class);
    }

    public function currentStocks(): HasMany
    {
        // Bảng tổng hợp tồn hiện tại của sản phẩm theo từng kho.
        return $this->hasMany(CurrentStock::class);
    }

    public function inventories(): HasMany
    {
        // Alias tương thích ngược với tên quan hệ cũ trong codebase.
        return $this->hasMany(Inventory::class);
    }
}
