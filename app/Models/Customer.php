<?php

namespace App\Models;

use App\Traits\HasNameSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperCustomer
 */
/**
 * Khách hàng thuộc một business.
 *
 * Đây là master data dùng để quản lý tệp khách hàng theo từng business.
 */
class Customer extends Model
{
    use HasNameSlug, SoftDeletes;

    protected $fillable = [
        'business_id',
        'name',
        'name_slug',
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

}
