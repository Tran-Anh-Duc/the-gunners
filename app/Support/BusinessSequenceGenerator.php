<?php

namespace App\Support;

use App\Services\SequenceService;
use Illuminate\Support\Str;

class BusinessSequenceGenerator
{
    /**
     * Compat helper cho các điểm gọi cũ.
     *
     * @param  class-string  $modelClass
     */
    public static function nextFormatted(
        string $modelClass,
        int $businessId,
        string $column,
        string $prefix,
        int $padding = 4,
        bool $prependBusinessCode = false,
    ): string
    {
        return app(SequenceService::class)->nextScopedCode(
            $businessId,
            self::scopeFor($modelClass, $column),
            $prefix,
            $padding,
            $prependBusinessCode,
        );
    }

    /**
     * Sinh tên scope ổn định theo model và tên cột.
     *
     * Ví dụ:
     * - `Order::class`, `order_no` => `order.order_no`
     * - `Unit::class`, `code` => `unit.code`
     */
    public static function scopeFor(string $modelClass, string $column): string
    {
        return Str::snake(class_basename($modelClass)).'.'.$column;
    }
}
