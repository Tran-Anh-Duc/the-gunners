<?php

namespace App\Repositories;

use App\Models\Warehouse;

class WarehouseRepository extends BaseBusinessRepository
{
    protected function modelClass(): string
    {
        return Warehouse::class;
    }
}
