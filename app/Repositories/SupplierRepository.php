<?php

namespace App\Repositories;

use App\Models\Supplier;

class SupplierRepository extends BaseBusinessRepository
{
    protected function modelClass(): string
    {
        return Supplier::class;
    }
}
