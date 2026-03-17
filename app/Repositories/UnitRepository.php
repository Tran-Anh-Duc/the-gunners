<?php

namespace App\Repositories;

use App\Models\Unit;

class UnitRepository extends BaseBusinessRepository
{
    protected function modelClass(): string
    {
        return Unit::class;
    }
}
