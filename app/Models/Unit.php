<?php

namespace App\Models;

use App\Support\BusinessSequenceGenerator;
use App\Traits\HasNameSlug;
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
 * `name` là nhãn nghiệp vụ để người dùng nhìn thấy, còn `code`
 * là mã nội bộ do hệ thống tự sinh trong phạm vi từng business.
 */
class Unit extends Model
{
    use HasNameSlug, SoftDeletes;

    protected $fillable = [
        'business_id',
        'code',
        'name',
        'name_slug',
        'description',
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
        static::creating(function (self $unit): void {
            if (! empty($unit->code) || empty($unit->business_id)) {
                return;
            }

            $unit->code = BusinessSequenceGenerator::nextFormatted(self::class, (int) $unit->business_id, 'code', 'UNIT');
        });
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
