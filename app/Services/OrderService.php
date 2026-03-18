<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Warehouse;
use App\Repositories\OrderRepository;
use App\Support\BusinessContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class OrderService extends BaseBusinessCrudService
{
    protected array $with = ['customer', 'warehouse', 'items.product', 'payments'];

    protected array $searchable = ['order_no', 'status', 'payment_status'];

    public function __construct(BusinessContext $businessContext, private readonly OrderRepository $orderRepository)
    {
        parent::__construct($businessContext);
        $this->repository = $orderRepository;
    }

    /**
     * Tạo đơn hàng mới.
     *
     * @param  array<string, mixed>  $data
     * @return Model
     *
     * Frontend có thể gửi `items`, `shipping_amount`, `note`...
     * nhưng các trường tiền tổng sẽ luôn do server tính lại từ item:
     * - subtotal
     * - tổng giảm giá
     * - total_amount
     */
    public function create(array $data): Model
    {
        /**
         * Tạo đơn hàng theo hướng "server tính toán lại".
         *
         * Đây là lớp bảo vệ quan trọng cho nghiệp vụ bán hàng:
         * frontend có thể gửi item, nhưng giá trị tiền sẽ được dựng lại ở backend
         * để tránh sai số, gian lận hoặc lệch dữ liệu khi client sửa tay.
         */
        $businessId = $this->resolveBusinessId($data);

        return DB::transaction(function () use ($businessId, $data) {
            // Header và các tham chiếu liên quan đều phải nằm trong cùng business.
            $this->assertBelongsToBusiness(Warehouse::class, $businessId, (int) $data['warehouse_id'], 'warehouse_id');
            $this->assertBelongsToBusiness(Customer::class, $businessId, $data['customer_id'] ?? null, 'customer_id');

            // Đơn hàng luôn tính lại tổng tiền từ item, không tin các số tổng do client gửi lên.
            [$itemsPayload, $subtotal, $discountAmount] = $this->buildOrderItems($businessId, $data['items']);
            $shippingAmount = (float) ($data['shipping_amount'] ?? 0);

            $order = $this->orderRepository->createForBusiness($businessId, [
                'warehouse_id' => $data['warehouse_id'],
                'customer_id' => $data['customer_id'] ?? null,
                'created_by' => $this->currentUserId(),
                'order_no' => $data['order_no'] ?? $this->nextDocumentNumber(Order::class, $businessId, 'order_no', 'ORD'),
                'order_date' => $data['order_date'] ?? now(),
                'status' => $data['status'] ?? 'draft',
                'payment_status' => $data['payment_status'] ?? 'unpaid',
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'shipping_amount' => $shippingAmount,
                'total_amount' => $subtotal - $discountAmount + $shippingAmount,
                'paid_amount' => 0,
                'note' => $data['note'] ?? null,
            ]);

            $this->orderRepository->replaceItems($order, $businessId, $itemsPayload);

            return $this->orderRepository->findForBusiness($businessId, $order->id, $this->with);
        });
    }

    /**
     * Cập nhật đơn hàng.
     *
     * @param  int  $id
     * @param  array<string, mixed>  $data
     * @return Model
     *
     * Nếu request không gui `items`, service se đúng items hiện tại của don
     * để tính lại tổng tiền trong bối cảnh header mới.
     */
    public function update(int $id, array $data): Model
    {
        // Cập nhật vẫn đi theo triết lý của create: kiểm tra tenant và dựng lại số tiền ở backend.
        $businessId = $this->resolveBusinessId($data);

        return DB::transaction(function () use ($businessId, $id, $data) {
            /** @var Order $order */
            $order = $this->orderRepository->findForBusiness($businessId, $id, ['items.product']);

            $warehouseId = (int) ($data['warehouse_id'] ?? $order->warehouse_id);
            $customerId = $data['customer_id'] ?? $order->customer_id;

            $this->assertBelongsToBusiness(Warehouse::class, $businessId, $warehouseId, 'warehouse_id');
            $this->assertBelongsToBusiness(Customer::class, $businessId, $customerId, 'customer_id');

            $itemsData = $data['items'] ?? $order->items->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'discount_amount' => $item->discount_amount,
                ];
            })->all();

            [$itemsPayload, $subtotal, $discountAmount] = $this->buildOrderItems($businessId, $itemsData);
            $shippingAmount = (float) ($data['shipping_amount'] ?? $order->shipping_amount);

            $this->orderRepository->updateRecord($order, [
                'warehouse_id' => $warehouseId,
                'customer_id' => $customerId,
                'order_no' => $data['order_no'] ?? $order->order_no,
                'order_date' => $data['order_date'] ?? $order->order_date,
                'status' => $data['status'] ?? $order->status,
                'payment_status' => $data['payment_status'] ?? $order->payment_status,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'shipping_amount' => $shippingAmount,
                'total_amount' => $subtotal - $discountAmount + $shippingAmount,
                'note' => $data['note'] ?? $order->note,
            ]);

            if (array_key_exists('items', $data)) {
                $this->orderRepository->replaceItems($order, $businessId, $itemsPayload);
            }

            return $this->orderRepository->findForBusiness($businessId, $order->id, $this->with);
        });
    }

    public function confirm(int $id, array $data): Model
    {
        return $this->transitionStatus($id, $data, 'confirmed');
    }

    public function cancel(int $id, array $data): Model
    {
        return $this->transitionStatus($id, $data, 'cancelled');
    }

    /**
     * Dựng snapshot item của đơn hàng.
     *
     * @param  int  $businessId
     * @param  array<int, array<string, mixed>>  $items
     * @return array{0: array<int, array<string, mixed>>, 1: float, 2: float}
     *
     * Kết quả:
     * - index 0: payload item để lưu DB
     * - index 1: subtotal trước discount
     * - index 2: tổng discount của tất cả dòng
     */
    protected function buildOrderItems(int $businessId, array $items): array
    {
        /**
         * Chuyển item frontend gửi lên thành snapshot item để lưu DB.
         *
         * Snapshot giúp:
         * - đơn cũ không bị đổi theo dữ liệu catalog mới;
         * - giá bán tại thời điểm chốt đơn được giữ nguyên để đối soát.
         */
        $payloads = [];
        $subtotal = 0;
        $discountAmount = 0;

        foreach ($items as $item) {
            // Chụp lại SKU, tên và giá tại thời điểm phát sinh để lịch sử bán hàng không bị méo sau này.
            /** @var Product $product */
            $product = Product::query()
                ->where('business_id', $businessId)
                ->findOrFail($item['product_id']);

            $unitPrice = (float) ($item['unit_price'] ?? $product->sale_price);
            $lineDiscount = (float) ($item['discount_amount'] ?? 0);
            $quantity = (float) $item['quantity'];

            $payloads[] = [
                'product_id' => $product->id,
                'product_sku' => $product->sku,
                'product_name' => $product->name,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount_amount' => $lineDiscount,
                'line_total' => ($quantity * $unitPrice) - $lineDiscount,
            ];

            $subtotal += $quantity * $unitPrice;
            $discountAmount += $lineDiscount;
        }

        return [$payloads, $subtotal, $discountAmount];
    }

    /**
     * Đổi trạng thái đơn hàng.
     *
     * @param  int  $id
     * @param  array<string, mixed>  $data
     * @param  string  $status
     * @return Model
     *
     * Hiện tại method này còn đơn giản, nhưng là điểm mở rộng sau này cho:
     * - state machine
     * - audit log
     * - rule chỉ cho phép confirm từ `draft`
     */
    protected function transitionStatus(int $id, array $data, string $status): Model
    {
        /**
         * MVP chưa có state machine đầy đủ.
         *
         * Việc gom confirm/cancel về đây giúp sau này dễ bổ sung luật chuyển trạng thái,
         * ghi nhận audit log hoặc trigger thêm side effect khác.
         */
        // Hiện tại cho đổi trực tiếp; nếu cần workflow chặt hơn thì siết ở đúng điểm này.
        $businessId = $this->resolveBusinessId($data);
        $order = $this->orderRepository->findForBusiness($businessId, $id, $this->with);
        $this->orderRepository->updateRecord($order, ['status' => $status]);

        return $this->orderRepository->findForBusiness($businessId, $order->id, $this->with);
    }
}
