<?php

namespace App\Repositories;

use App\Models\WarehouseDocumentDetail;

class WarehouseDocumentDetailRepository extends BaseRepository
{
    public function getModel()
    {
        return WarehouseDocumentDetail::class;
    }
}
