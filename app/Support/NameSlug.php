<?php

namespace App\Support;

use Illuminate\Support\Str;

final class NameSlug
{
    public static function from(?string $value): string
    {
        return Str::slug((string) $value);
    }
}
