<?php

namespace App\Traits;

use App\Support\NameSlug;

trait HasNameSlug
{
    protected static function bootHasNameSlug(): void
    {
        static::saving(function ($model): void {
            if (! array_key_exists('name', $model->getAttributes()) && ! $model->isDirty('name')) {
                return;
            }

            $name = $model->getAttribute('name');

            if ($name === null) {
                return;
            }

            $model->setAttribute('name_slug', NameSlug::from((string) $name));
        });
    }
}
