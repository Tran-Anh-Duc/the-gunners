<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Nhà cung cấp thuộc một business.
 *
 * Supplier được dùng trong:
 * - chứng từ nhập kho;
 * - phiếu chi hoặc thanh toán ra;
 * - các báo cáo mua hàng hoặc công nợ phải trả về sau.
 */
class Supplier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'business_id',
        'name',
        'contact_name',
        'phone',
        'email',
        'address',
        'note',
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
        // Xác định tenant sở hữu nhà cung cấp này.
        return $this->belongsTo(Business::class);
    }

    public function stockIns(): HasMany
    {
        // Các phiếu nhập mua hàng từ nhà cung cấp này.
        return $this->hasMany(StockIn::class);
    }

    public function payments(): HasMany
    {
        // Các phiếu chi hoặc thanh toán ra cho nhà cung cấp này.
        return $this->hasMany(Payment::class);
    }
}
