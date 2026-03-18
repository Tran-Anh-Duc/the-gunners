<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperUnit
 */
/**
 * Đơn vị tính có scope theo business.
 *
 * Cách làm này cho phép mỗi shop tự định nghĩa mã và tên đơn vị tính
 * theo quy ước riêng của mình mà không va chạm dữ liệu tenant khác.
 */
class Unit extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'business_id',
        'code',
        'name',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function business(): BelongsTo
    {
        // Xác định business sở hữu đơn vị tính này.
        return $this->belongsTo(Business::class);
    }

    public function products(): HasMany
    {
        // Các sản phẩm đang dùng đơn vị tính này làm đơn vị mặc định.
        return $this->hasMany(Product::class);
    }
}
