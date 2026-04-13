<?php

namespace App\Models;

use App\Traits\HasNameSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Nhà cung cấp thuộc một business.
 *
 * Supplier được dùng để quản lý danh bạ nhà cung cấp của từng business.
 */
class Supplier extends Model
{
    use HasNameSlug, SoftDeletes;

    protected $fillable = [
        'business_id',
        'name',
        'name_slug',
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

}
