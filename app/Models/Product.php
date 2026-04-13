<?php

namespace App\Models;

use App\Traits\HasNameSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperProduct
 */
/**
 * Sản phẩm của business.
 *
 * Ở giai đoạn MVP, sản phẩm được giữ dưới dạng simple product, chưa tách variant.
 * Cách mô hình hóa này giúp catalog và master data dễ triển khai hơn:
 * - một sản phẩm gắn với một đơn vị tính mặc định;
 * - SKU được quản lý ổn định theo từng business;
 * - có thể mở rộng thêm nghiệp vụ ở các pha sau mà không đổi lõi catalog.
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

}
