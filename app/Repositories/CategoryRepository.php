<?php

namespace App\Repositories;

use App\Models\Category;

class CategoryRepository extends BaseBusinessRepository
{
    protected function modelClass(): string
    {
        return Category::class;
    }
}
