<?php

namespace App\Repositories;

use App\Models\Payment;

class PaymentRepository extends BaseBusinessRepository
{
    protected function modelClass(): string
    {
        return Payment::class;
    }

    public function paidAmountForOrder(int $orderId): float
    {
        return (float) Payment::query()
            ->where('order_id', $orderId)
            ->where('direction', 'in')
            ->where('status', 'paid')
            ->sum('amount');
    }
}
