<?php

namespace App\Repositories;

use App\Models\CurrentStock;

/**
 * Repository đọc bảng `current_stocks`.
 *
 * Inventory view của giao diện đọc từ bảng tổng hợp này
 * thay vì query thẳng ledger để tối ưu tốc độ hiển thị.
 */
class InventoryRepository extends BaseBusinessRepository
{
    /**
     * @return class-string
     */
    protected function modelClass(): string
    {
        return CurrentStock::class;
    }
}
