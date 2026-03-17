<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\OrderItem;

class OrderRepository extends BaseBusinessRepository
{
    protected function modelClass(): string
    {
        return Order::class;
    }

    public function replaceItems(Order $order, int $businessId, array $itemsPayload): void
    {
        $order->items()->delete();

        foreach ($itemsPayload as $payload) {
            OrderItem::query()->create(array_merge($payload, [
                'business_id' => $businessId,
                'order_id' => $order->id,
            ]));
        }
    }
}
