<?php

namespace App\Support;

class BusinessSequenceGenerator
{
    /**
     * Sinh mã tăng dần trong phạm vi từng business.
     *
     * @param  class-string  $modelClass
     */
    public static function nextFormatted(string $modelClass, int $businessId, string $column, string $prefix): string
    {
        $sequence = $modelClass::query()
            ->where('business_id', $businessId)
            ->count() + 1;

        do {
            $candidate = sprintf('%s-%04d', $prefix, $sequence);
            $exists = $modelClass::query()
                ->where('business_id', $businessId)
                ->where($column, $candidate)
                ->exists();
            $sequence++;
        } while ($exists);

        return $candidate;
    }
}
