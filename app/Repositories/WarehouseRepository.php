<?php

namespace App\Repositories;

use App\Models\Warehouse;

/**
 * Repository warehouse theo business.
 *
 * Sau này có thể thêm query theo kho mặc định,
 * kho hoạt động hoặc các rule lọc riêng tại đây.
 */
class WarehouseRepository extends BaseBusinessRepository
{
    protected function modelClass(): string
    {
        return Warehouse::class;
    }
}
