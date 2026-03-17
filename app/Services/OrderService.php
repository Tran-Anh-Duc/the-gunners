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

    public function create(array $data): Model
    {
        $businessId = $this->resolveBusinessId($data);

        return DB::transaction(function () use ($businessId, $data) {
            $this->assertBelongsToBusiness(Warehouse::class, $businessId, (int) $data['warehouse_id'], 'warehouse_id');
            $this->assertBelongsToBusiness(Customer::class, $businessId, $data['customer_id'] ?? null, 'customer_id');

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

    public function update(int $id, array $data): Model
    {
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

    protected function buildOrderItems(int $businessId, array $items): array
    {
        $payloads = [];
        $subtotal = 0;
        $discountAmount = 0;

        foreach ($items as $item) {
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

    protected function transitionStatus(int $id, array $data, string $status): Model
    {
        $businessId = $this->resolveBusinessId($data);
        $order = $this->orderRepository->findForBusiness($businessId, $id, $this->with);
        $this->orderRepository->updateRecord($order, ['status' => $status]);

        return $this->orderRepository->findForBusiness($businessId, $order->id, $this->with);
    }
}
