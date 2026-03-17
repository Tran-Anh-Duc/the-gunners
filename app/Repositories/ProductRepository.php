<?php

namespace App\Repositories;

use App\Models\Product;

class ProductRepository extends BaseBusinessRepository
{
    protected function modelClass(): string
    {
        return Product::class;
    }
}
