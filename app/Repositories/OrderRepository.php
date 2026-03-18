<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\OrderItem;

/**
 * Repository đơn hàng.
 *
 * Chịu trách nhiệm lưu header và item của order,
 * không chứa logic tính tổng tiền hay workflow trạng thái.
 */
class OrderRepository extends BaseBusinessRepository
{
    /**
     * @return class-string
     */
    protected function modelClass(): string
    {
        return Order::class;
    }

    /**
     * Thay thế toàn bộ order item.
     *
     * @param  Order  $order
     * @param  int  $businessId
     * @param  array<int, array<string, mixed>>  $itemsPayload
     */
    public function replaceItems(Order $order, int $businessId, array $itemsPayload): void
    {
        // MVP chọn chiến lược replace all để code dễ đọc và tránh sync sai diff item.
        $order->items()->delete();

        foreach ($itemsPayload as $payload) {
            OrderItem::query()->create(array_merge($payload, [
                'business_id' => $businessId,
                'order_id' => $order->id,
            ]));
        }
    }
}
