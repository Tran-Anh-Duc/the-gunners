<?php

namespace App\Repositories;

use App\Models\Supplier;

/**
 * Repository supplier theo business.
 *
 * Được tách riêng từ sớm để sau này dễ mở rộng query công nợ
 * hoặc lịch sử nhập hàng của nhà cung cấp.
 */
class SupplierRepository extends BaseBusinessRepository
{
    protected function modelClass(): string
    {
        return Supplier::class;
    }
}
