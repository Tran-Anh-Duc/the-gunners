<?php

namespace App\Repositories;

use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;

/**
 * Repository stock adjustment.
 *
 * Gom helper replace item để service không phải biết chi tiết persistence.
 */
class StockAdjustmentRepository extends BaseBusinessRepository
{
    /**
     * @return class-string
     */
    protected function modelClass(): string
    {
        return StockAdjustment::class;
    }

    /**
     * Thay thế toàn bộ item của document adjustment.
     *
     * @param  StockAdjustment  $stockAdjustment
     * @param  int  $businessId
     * @param  array<int, array<string, mixed>>  $itemsPayload
     *
     * Chiến lược "replace all" phù hợp với MVP:
     * - dễ đọc;
     * - tránh sync sai giữa item cũ và item mới.
     */
    public function replaceItems(StockAdjustment $stockAdjustment, int $businessId, array $itemsPayload): void
    {
        // MVP chọn cách xóa hết và tạo lại item để dễ maintain khi user sửa document.
        $stockAdjustment->items()->delete();

        foreach ($itemsPayload as $payload) {
            StockAdjustmentItem::query()->create(array_merge($payload, [
                'business_id' => $businessId,
                'stock_adjustment_id' => $stockAdjustment->id,
            ]));
        }
    }
}
