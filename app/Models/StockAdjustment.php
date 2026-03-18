<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Header chứng từ kiểm kho/điều chỉnh tồn.
 *
 * Document nay đúng để dua ton hệ thống ve ton thực tế sau khi kiểm kho.
 */
class StockAdjustment extends Model
{
    protected $fillable = [
        'business_id',
        'warehouse_id',
        'created_by',
        'adjustment_no',
        'adjustment_date',
        'reason',
        'status',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'adjustment_date' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        // Chứng từ nay thuộc business nào.
        return $this->belongsTo(Business::class);
    }

    public function warehouse(): BelongsTo
    {
        // Kiểm kho xay ra tai kho nào.
        return $this->belongsTo(Warehouse::class);
    }

    public function creator(): BelongsTo
    {
        // User lap chứng từ kiểm kho.
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        // Cac dòng chênh lệch ton của chứng từ.
        return $this->hasMany(StockAdjustmentItem::class);
    }
}
