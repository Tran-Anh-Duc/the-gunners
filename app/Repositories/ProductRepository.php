<?php

namespace App\Repositories;

use App\Models\Product;

/**
 * Repository product theo business.
 *
 * Chua co query dac thu o MVP, nhưng là diem hop ly để them tim SKU/barcođể sau này.
 */
class ProductRepository extends BaseBusinessRepository
{
    /**
     * Trả ve model class mà repository quản lý.
     *
     * @return class-string
     */
    protected function modelClass(): string
    {
        return Product::class;
    }
}
