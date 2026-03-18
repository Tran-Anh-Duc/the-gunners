<?php

namespace App\Repositories;

use App\Models\Unit;

/**
 * Repository unit theo business.
 *
 * Lop nay hien mỏng, nhưng giữ chuan repo pattern dòng deu với toan project.
 */
class UnitRepository extends BaseBusinessRepository
{
    protected function modelClass(): string
    {
        return Unit::class;
    }
}
