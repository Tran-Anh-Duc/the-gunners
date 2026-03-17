<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\StockIn;
use App\Models\Supplier;
use App\Repositories\PaymentRepository;
use App\Support\BusinessContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PaymentService extends BaseBusinessCrudService
{
    protected array $with = ['order', 'stockIn', 'customer', 'supplier'];

    protected array $searchable = ['payment_no', 'status', 'method', 'direction'];

    public function __construct(BusinessContext $businessContext, private readonly PaymentRepository $paymentRepository)
    {
        parent::__construct($businessContext);
        $this->repository = $paymentRepository;
    }

    public function create(array $data): Model
    {
        $businessId = $this->resolveBusinessId($data);

        return DB::transaction(function () use ($businessId, $data) {
            $this->assertPaymentRelations($businessId, $data);

            $payment = $this->paymentRepository->createForBusiness($businessId, [
                'order_id' => $data['order_id'] ?? null,
                'stock_in_id' => $data['stock_in_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'supplier_id' => $data['supplier_id'] ?? null,
                'created_by' => $this->currentUserId(),
                'payment_no' => $data['payment_no'] ?? $this->nextDocumentNumber(Payment::class, $businessId, 'payment_no', 'PAY'),
                'direction' => $data['direction'] ?? 'in',
                'method' => $data['method'] ?? 'cash',
                'status' => $data['status'] ?? 'paid',
                'amount' => $data['amount'],
                'payment_date' => $data['payment_date'] ?? now(),
                'reference_no' => $data['reference_no'] ?? null,
                'note' => $data['note'] ?? null,
            ]);

            $this->syncOrderPaymentSummary($payment->order_id);

            return $this->paymentRepository->findForBusiness($businessId, $payment->id, $this->with);
        });
    }

    public function update(int $id, array $data): Model
    {
        $businessId = $this->resolveBusinessId($data);

        return DB::transaction(function () use ($businessId, $id, $data) {
            /** @var Payment $payment */
            $payment = $this->paymentRepository->findForBusiness($businessId, $id, ['order']);
            $previousOrderId = $payment->order_id;
            $mergedData = array_merge($payment->toArray(), $data);

            $this->assertPaymentRelations($businessId, $mergedData);

            $this->paymentRepository->updateRecord($payment, [
                'order_id' => $data['order_id'] ?? $payment->order_id,
                'stock_in_id' => $data['stock_in_id'] ?? $payment->stock_in_id,
                'customer_id' => $data['customer_id'] ?? $payment->customer_id,
                'supplier_id' => $data['supplier_id'] ?? $payment->supplier_id,
                'payment_no' => $data['payment_no'] ?? $payment->payment_no,
                'direction' => $data['direction'] ?? $payment->direction,
                'method' => $data['method'] ?? $payment->method,
                'status' => $data['status'] ?? $payment->status,
                'amount' => $data['amount'] ?? $payment->amount,
                'payment_date' => $data['payment_date'] ?? $payment->payment_date,
                'reference_no' => $data['reference_no'] ?? $payment->reference_no,
                'note' => $data['note'] ?? $payment->note,
            ]);

            $this->syncOrderPaymentSummary($previousOrderId);
            $this->syncOrderPaymentSummary($payment->order_id);

            return $this->paymentRepository->findForBusiness($businessId, $payment->id, $this->with);
        });
    }

    public function confirm(int $id, array $data): Model
    {
        return $this->transitionStatus($id, $data, 'paid');
    }

    public function cancel(int $id, array $data): Model
    {
        return $this->transitionStatus($id, $data, 'cancelled');
    }

    protected function assertPaymentRelations(int $businessId, array $data): void
    {
        $this->assertBelongsToBusiness(Order::class, $businessId, $data['order_id'] ?? null, 'order_id');
        $this->assertBelongsToBusiness(StockIn::class, $businessId, $data['stock_in_id'] ?? null, 'stock_in_id');
        $this->assertBelongsToBusiness(Customer::class, $businessId, $data['customer_id'] ?? null, 'customer_id');
        $this->assertBelongsToBusiness(Supplier::class, $businessId, $data['supplier_id'] ?? null, 'supplier_id');
    }

    protected function syncOrderPaymentSummary(?int $orderId): void
    {
        if ($orderId === null) {
            return;
        }

        /** @var Order|null $order */
        $order = Order::query()->find($orderId);

        if (! $order) {
            return;
        }

        $paidAmount = $this->paymentRepository->paidAmountForOrder($orderId);
        $paymentStatus = 'unpaid';

        if ($paidAmount > 0 && $paidAmount < (float) $order->total_amount) {
            $paymentStatus = 'partial';
        } elseif ($paidAmount >= (float) $order->total_amount && (float) $order->total_amount > 0) {
            $paymentStatus = 'paid';
        }

        $order->update([
            'paid_amount' => $paidAmount,
            'payment_status' => $paymentStatus,
        ]);
    }

    protected function transitionStatus(int $id, array $data, string $status): Model
    {
        $businessId = $this->resolveBusinessId($data);

        return DB::transaction(function () use ($businessId, $id, $status) {
            /** @var Payment $payment */
            $payment = $this->paymentRepository->findForBusiness($businessId, $id, $this->with);
            $this->paymentRepository->updateRecord($payment, ['status' => $status]);
            $this->syncOrderPaymentSummary($payment->order_id);

            return $this->paymentRepository->findForBusiness($businessId, $payment->id, $this->with);
        });
    }
}
