<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Danh sách module được bat cho từng business.
 *
 * Bang nay phuc vu bai toan SaaS "an/hien module theo goi",
 * không phải he permission chỉ tiết.
 */
class BusinessModule extends Model
{
    protected $fillable = [
        'business_id',
        'module_code',
        'status',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        // Module nay dang được bat cho business nào.
        return $this->belongsTo(Business::class);
    }
}
