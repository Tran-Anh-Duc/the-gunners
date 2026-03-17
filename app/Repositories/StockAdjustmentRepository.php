<?php

namespace App\Repositories;

use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;

class StockAdjustmentRepository extends BaseBusinessRepository
{
    protected function modelClass(): string
    {
        return StockAdjustment::class;
    }

    public function replaceItems(StockAdjustment $stockAdjustment, int $businessId, array $itemsPayload): void
    {
        $stockAdjustment->items()->delete();

        foreach ($itemsPayload as $payload) {
            StockAdjustmentItem::query()->create(array_merge($payload, [
                'business_id' => $businessId,
                'stock_adjustment_id' => $stockAdjustment->id,
            ]));
        }
    }
}
