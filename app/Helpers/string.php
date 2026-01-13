<?php

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

if (!function_exists('normalize_slug_search')) {
    function normalize_slug_search(string $input): string
    {
        $input = Str::ascii($input);          // bỏ dấu
        $input = strtolower(trim($input));    // lowercase + trim
        $input = str_replace(' ', '_', $input); // space -> _
        return addcslashes($input, '_%');     // escape SQL wildcard
    }
}
if (!function_exists('generate_unique_slug')) {
    function generate_unique_slug(
        string|Model $model,
        string $value,
        string $column = 'slug',
        ?int $ignoreId = null
    ): string {
        $modelClass = $model instanceof Model ? get_class($model) : $model;

        $baseSlug = Str::of($value)
            ->ascii()
            ->lower()
            ->replace(' ', '_')
            ->toString();

        $slug = $baseSlug;
        $counter = 1;

        while (
        $modelClass::where($column, $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $slug = $baseSlug . '_' . $counter;
            $counter++;
        }

        return $slug;
    }
}
