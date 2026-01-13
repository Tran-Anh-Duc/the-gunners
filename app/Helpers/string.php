<?php

use Illuminate\Support\Str;

if (!function_exists('normalize_slug_search')) {
    function normalize_slug_search(string $input): string
    {
        $input = Str::ascii($input);          // bỏ dấu
        $input = strtolower(trim($input));    // lowercase + trim
        $input = str_replace(' ', '_', $input); // space -> _
        return addcslashes($input, '_%');     // escape SQL wildcard
    }
}
