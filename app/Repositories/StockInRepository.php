<?php

namespace App\Repositories;

use App\Models\StockIn;
use App\Models\StockInItem;

class StockInRepository extends BaseBusinessRepository
{
    protected function modelClass(): string
    {
        return StockIn::class;
    }

    public function replaceItems(StockIn $stockIn, int $businessId, array $itemsPayload): void
    {
        $stockIn->items()->delete();

        foreach ($itemsPayload as $payload) {
            StockInItem::query()->create(array_merge($payload, [
                'business_id' => $businessId,
                'stock_in_id' => $stockIn->id,
            ]));
        }
    }
}
