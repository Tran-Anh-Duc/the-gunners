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

    /**
     * Tạo payment mới.
     *
     * @param  array<string, mixed>  $data
     * @return Model
     *
     * Đầu vào thường có dạng:
     * [
     *   'order_id' => 12,
     *   'amount' => 500000,
     *   'direction' => 'in',
     *   'status' => 'paid'
     * ]
     *
     * Sau khi lưu payment, method sẽ gọi `syncOrderPaymentSummary()` nếu payment
     * có liên kết đến order để cập nhật `paid_amount` và `payment_status`.
     */
    public function create(array $data): Model
    {
        /**
         * Tạo phiếu thu/chi và cập nhật payment summary nếu có liên kết order.
         *
         * Payment không được tham chiếu sang business khác,
         * nên service phải kiểm tra tất cả khóa ngoại liên quan trước khi lưu.
         */
        $businessId = $this->resolveBusinessId($data);

        return DB::transaction(function () use ($businessId, $data) {
            // Mỗi liên kết tham chiếu như order, customer, supplier... đều phải thuộc cùng business.
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

    /**
     * Cập nhật payment.
     *
     * @param  int  $id  ID payment cần sua
     * @param  array<string, mixed>  $data  Payload đã qua FormRequest
     * @return Model
     *
     * Lưu ý:
     * - Nếu payment được chuyển từ order A sang order B, service sẽ sync summary cho cả 2 order.
     * - `$mergedData` được tạo ra để validate trong trạng thái "sau khi merge"
     *   thay vì chỉ validate từng field lẻ.
     */
    public function update(int $id, array $data): Model
    {
        // Sửa payment có thể làm thay đổi order liên quan, nên cần sync cả order cũ và mới.
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

    /**
     * Kiểm tra các khóa ngoại liên quan đến payment có cùng thuộc một business.
     *
     * @param  int  $businessId
     * @param  array<string, mixed>  $data
     *
     * Đây là lớp chặn quan trọng để tránh các trường hợp như:
     * - tạo payment của business A nhưng trỏ vào order của business B;
     * - hoặc liên kết nhầm supplier/customer khác tenant.
     */
    protected function assertPaymentRelations(int $businessId, array $data): void
    {
        // Đây là lớp chặn việc "tenant leak" cho các liên kết quanh payment.
        $this->assertBelongsToBusiness(Order::class, $businessId, $data['order_id'] ?? null, 'order_id');
        $this->assertBelongsToBusiness(StockIn::class, $businessId, $data['stock_in_id'] ?? null, 'stock_in_id');
        $this->assertBelongsToBusiness(Customer::class, $businessId, $data['customer_id'] ?? null, 'customer_id');
        $this->assertBelongsToBusiness(Supplier::class, $businessId, $data['supplier_id'] ?? null, 'supplier_id');
    }

    /**
     * Cập nhật tổng đã thu và payment status của order.
     *
     * @param  int|null  $orderId
     *
     * Quy ước:
     * - `unpaid`: chưa thu dòng nào;
     * - `partial`: đã thu một phần;
     * - `paid`: đã thu đủ hoặc vượt tổng tiền đơn.
     *
     * Ví dụ:
     * - order total = 1.000.000
     * - đã thu 300.000 => `partial`
     * - đã thu 1.000.000 => `paid`
     */
    protected function syncOrderPaymentSummary(?int $orderId): void
    {
        /**
         * Tính lại `paid_amount` và `payment_status` của order.
         *
         * Không dùng trigger DB để giữ logic tập trung ở ứng dụng Laravel,
         * giúp dễ đọc, dễ test và dễ bảo trì hơn trong MVP.
         */
        if ($orderId === null) {
            return;
        }

        /** @var Order|null $order */
        $order = Order::query()->find($orderId);

        if (! $order) {
            return;
        }

        // MVP suy ra payment_status của đơn hàng từ tổng đã thu, không đẩy logic xuống DB.
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

    /**
     * Đổi trạng thái payment.
     *
     * @param  int  $id
     * @param  array<string, mixed>  $data
     * @param  string  $status  Trạng thái đích, ví dụ `paid` hoặc `cancelled`
     * @return Model
     *
     * Hàm này được dùng chung bởi `confirm()` và `cancel()`.
     */
    protected function transitionStatus(int $id, array $data, string $status): Model
    {
        // Việc chuyển trạng thái không xóa payment mà cập nhật lại summary của đơn hàng liên quan.
        $businessId = $this->resolveBusinessId($data);

        return DB::transaction(function () use ($businessId, $id, $status) {
            // Confirm hoặc cancel payment có thể làm thay đổi tổng đã thu của order nên phải sync lại summary.
            /** @var Payment $payment */
            $payment = $this->paymentRepository->findForBusiness($businessId, $id, $this->with);
            $this->paymentRepository->updateRecord($payment, ['status' => $status]);
            $this->syncOrderPaymentSummary($payment->order_id);

            return $this->paymentRepository->findForBusiness($businessId, $payment->id, $this->with);
        });
    }
}
