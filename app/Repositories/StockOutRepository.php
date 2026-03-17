<?php

namespace App\Repositories;

use App\Models\StockOut;
use App\Models\StockOutItem;

class StockOutRepository extends BaseBusinessRepository
{
    protected function modelClass(): string
    {
        return StockOut::class;
    }

    public function replaceItems(StockOut $stockOut, int $businessId, array $itemsPayload): void
    {
        $stockOut->items()->delete();

        foreach ($itemsPayload as $payload) {
            StockOutItem::query()->create(array_merge($payload, [
                'business_id' => $businessId,
                'stock_out_id' => $stockOut->id,
            ]));
        }
    }
}
