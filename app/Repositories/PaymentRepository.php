<?php

namespace App\Repositories;

use App\Models\Payment;

/**
 * Repository payment.
 *
 * Ngoài CRUD cơ bản, repository này có thêm helper tính tổng đã thu cho order
 * để service cập nhật `payment_status` ở tầng ứng dụng.
 */
class PaymentRepository extends BaseBusinessRepository
{
    /**
     * @return class-string
     */
    protected function modelClass(): string
    {
        return Payment::class;
    }

    /**
     * Tính tổng tiền đã thu của một order.
     *
     * @param  int  $orderId
     * @return float
     *
     * Chỉ tính payment:
     * - `direction = in`
     * - `status = paid`
     */
    public function paidAmountForOrder(int $orderId): float
    {
        // Chỉ cộng các payment hướng `in` và đã `paid` để tránh tính nhầm draft hoặc cancelled.
        return (float) Payment::query()
            ->where('order_id', $orderId)
            ->where('direction', 'in')
            ->where('status', 'paid')
            ->sum('amount');
    }
}
