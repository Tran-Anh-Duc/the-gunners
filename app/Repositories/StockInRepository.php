<?php

namespace App\Repositories;

use App\Models\StockIn;
use App\Models\StockInItem;

/**
 * Repository phiếu nhập kho.
 *
 * Chịu trách nhiệm persistence phần header và item cho `stock_in`,
 * còn quy tắc ledger nằm ở service.
 */
class StockInRepository extends BaseBusinessRepository
{
    /**
     * @return class-string
     */
    protected function modelClass(): string
    {
        return StockIn::class;
    }

    /**
     * Thay thế toàn bộ item của phiếu nhập.
     *
     * @param  StockIn  $stockIn
     * @param  int  $businessId
     * @param  array<int, array<string, mixed>>  $itemsPayload
     */
    public function replaceItems(StockIn $stockIn, int $businessId, array $itemsPayload): void
    {
        // Đơn giản hóa update item bằng cách xóa toàn bộ rồi tạo lại từ payload mới.
        $stockIn->items()->delete();

        foreach ($itemsPayload as $payload) {
            StockInItem::query()->create(array_merge($payload, [
                'business_id' => $businessId,
                'stock_in_id' => $stockIn->id,
            ]));
        }
    }
}
