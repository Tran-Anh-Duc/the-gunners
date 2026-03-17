<?php

namespace App\Repositories;

use App\Models\CurrentStock;

class InventoryRepository extends BaseBusinessRepository
{
    protected function modelClass(): string
    {
        return CurrentStock::class;
    }
}
