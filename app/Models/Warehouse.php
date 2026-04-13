<?php

namespace App\Models;

use App\Support\BusinessSequenceGenerator;
use App\Traits\HasNameSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperWarehouse
 */
/**
 * Kho của business.
 *
 * Warehouse là master data quan trọng để nhóm sản phẩm theo điểm lưu trữ.
 */
class Warehouse extends Model
{
    use HasNameSlug, SoftDeletes;

    protected $fillable = [
        'business_id',
        'code',
        'name',
        'name_slug',
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

}
