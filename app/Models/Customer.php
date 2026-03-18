<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperCustomer
 */
/**
 * Khách hàng thuộc một business.
 *
 * Đây là master data dùng cho:
 * - đơn hàng bán ra;
 * - phiếu thu gắn với khách mua;
 * - các báo cáo doanh thu hoặc công nợ về sau.
 */
class Customer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'business_id',
        'name',
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
        // Xác định tenant sở hữu khách hàng này.
        return $this->belongsTo(Business::class);
    }

    public function orders(): HasMany
    {
        // Các đơn hàng bán ra cho khách hàng này.
        return $this->hasMany(Order::class);
    }

    public function payments(): HasMany
    {
        // Các phiếu thu liên quan đến khách hàng này.
        return $this->hasMany(Payment::class);
    }
}
