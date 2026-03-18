<?php

namespace App\Repositories;

use App\Models\StockOut;
use App\Models\StockOutItem;

/**
 * Repository phiếu xuất kho.
 *
 * Giữ tầng persistence tách khỏi nghiệp vụ giá vốn và tồn kho.
 */
class StockOutRepository extends BaseBusinessRepository
{
    /**
     * @return class-string
     */
    protected function modelClass(): string
    {
        return StockOut::class;
    }

    /**
     * Thay thế toàn bộ item của phiếu xuất.
     *
     * @param  StockOut  $stockOut
     * @param  int  $businessId
     * @param  array<int, array<string, mixed>>  $itemsPayload
     */
    public function replaceItems(StockOut $stockOut, int $businessId, array $itemsPayload): void
    {
        // Chiến lược replace toàn bộ item phù hợp với MVP và dễ theo dõi hơn diff item.
        $stockOut->items()->delete();

        foreach ($itemsPayload as $payload) {
            StockOutItem::query()->create(array_merge($payload, [
                'business_id' => $businessId,
                'stock_out_id' => $stockOut->id,
            ]));
        }
    }
}
