<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockOut;
use App\Models\Warehouse;
use App\Repositories\StockOutRepository;
use App\Support\BusinessContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StockOutService extends BaseBusinessCrudService
{
    protected array $with = ['warehouse', 'customer', 'order', 'items.product'];

    protected array $searchable = ['stock_out_no', 'status', 'stock_out_type', 'reference_no'];

    public function __construct(
        BusinessContext $businessContext,
        private readonly StockOutRepository $stockOutRepository,
        private readonly InventoryLedgerService $inventoryLedgerService,
    ) {
        parent::__construct($businessContext);
        $this->repository = $stockOutRepository;
    }

    /**
     * Tạo phiếu xuất kho.
     *
     * @param  array<string, mixed>  $data
     * @return Model
     *
     * Service sẽ kiểm tra warehouse, order, customer theo business,
     * dựng snapshot item rồi sync ledger để trừ tồn và tính giá vốn moving average.
     */
    public function create(array $data): Model
    {
        /**
         * Tạo chứng từ xuất kho.
         *
         * Sau khi tạo item và header, service gọi ledger để:
         * - trừ tồn;
         * - tính giá vốn bình quân;
         * - cập nhật `current_stocks`.
         */
        $businessId = $this->resolveBusinessId($data);

        return DB::transaction(function () use ($businessId, $data) {
            $this->assertBelongsToBusiness(Warehouse::class, $businessId, (int) $data['warehouse_id'], 'warehouse_id');
            $this->assertBelongsToBusiness(Order::class, $businessId, $data['order_id'] ?? null, 'order_id');
            $this->assertBelongsToBusiness(Customer::class, $businessId, $data['customer_id'] ?? null, 'customer_id');

            [$itemsPayload, $subtotal] = $this->buildItems($businessId, $data['items']);

            $stockOut = $this->stockOutRepository->createForBusiness($businessId, [
                'warehouse_id' => $data['warehouse_id'],
                'order_id' => $data['order_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'created_by' => $this->currentUserId(),
                'stock_out_no' => $data['stock_out_no'] ?? $this->nextDocumentNumber(StockOut::class, $businessId, 'stock_out_no', 'SO'),
                'reference_no' => $data['reference_no'] ?? null,
                'stock_out_type' => $data['stock_out_type'] ?? 'sale',
                'stock_out_date' => $data['stock_out_date'] ?? now(),
                'status' => $data['status'] ?? 'draft',
                'subtotal' => $subtotal,
                'total_amount' => $subtotal,
                'note' => $data['note'] ?? null,
            ]);

            $this->stockOutRepository->replaceItems($stockOut, $businessId, $itemsPayload);

            // Stock-out luôn đi qua ledger để tồn kho và giá vốn được tính theo cùng một nguồn sự thật.
            $stockOut = $this->stockOutRepository->findForBusiness($businessId, $stockOut->id, ['items.product']);
            $this->inventoryLedgerService->syncStockOut($stockOut);

            return $this->stockOutRepository->findForBusiness($businessId, $stockOut->id, $this->with);
        });
    }

    /**
     * Cập nhật phiếu xuất kho.
     *
     * @param  int  $id
     * @param  array<string, mixed>  $data
     * @return Model
     *
     * Sau khi cập nhật xong, movement của document sẽ được rebuild lại
     * để tránh sai tồn hoặc sai giá vốn.
     */
    public function update(int $id, array $data): Model
    {
        // Sửa xuất kho luôn phải rebuild movement vì quantity và giá vốn có thể thay đổi theo trạng thái mới.
        $businessId = $this->resolveBusinessId($data);

        return DB::transaction(function () use ($businessId, $id, $data) {
            /** @var StockOut $stockOut */
            $stockOut = $this->stockOutRepository->findForBusiness($businessId, $id, ['items.product']);

            $warehouseId = (int) ($data['warehouse_id'] ?? $stockOut->warehouse_id);
            $orderId = $data['order_id'] ?? $stockOut->order_id;
            $customerId = $data['customer_id'] ?? $stockOut->customer_id;

            $this->assertBelongsToBusiness(Warehouse::class, $businessId, $warehouseId, 'warehouse_id');
            $this->assertBelongsToBusiness(Order::class, $businessId, $orderId, 'order_id');
            $this->assertBelongsToBusiness(Customer::class, $businessId, $customerId, 'customer_id');

            $itemsData = $data['items'] ?? $stockOut->items->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                ];
            })->all();

            [$itemsPayload, $subtotal] = $this->buildItems($businessId, $itemsData);

            $this->stockOutRepository->updateRecord($stockOut, [
                'warehouse_id' => $warehouseId,
                'order_id' => $orderId,
                'customer_id' => $customerId,
                'stock_out_no' => $data['stock_out_no'] ?? $stockOut->stock_out_no,
                'reference_no' => $data['reference_no'] ?? $stockOut->reference_no,
                'stock_out_type' => $data['stock_out_type'] ?? $stockOut->stock_out_type,
                'stock_out_date' => $data['stock_out_date'] ?? $stockOut->stock_out_date,
                'status' => $data['status'] ?? $stockOut->status,
                'subtotal' => $subtotal,
                'total_amount' => $subtotal,
                'note' => $data['note'] ?? $stockOut->note,
            ]);

            if (array_key_exists('items', $data)) {
                $this->stockOutRepository->replaceItems($stockOut, $businessId, $itemsPayload);
            }

            $stockOut = $this->stockOutRepository->findForBusiness($businessId, $stockOut->id, ['items.product']);
            $this->inventoryLedgerService->syncStockOut($stockOut);

            return $this->stockOutRepository->findForBusiness($businessId, $stockOut->id, $this->with);
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
     * Chuẩn hóa item xuất kho.
     *
     * @param  int  $businessId
     * @param  array<int, array<string, mixed>>  $items
     * @return array{0: array<int, array<string, mixed>>, 1: float}
     *
     * Lưu ý:
     * - `unit_price` là giá bán để tính doanh thu trên chứng từ;
     * - giá vốn xuất thực tế không tính ở đây mà tính trong ledger.
     */
    protected function buildItems(int $businessId, array $items): array
    {
        /**
         * Snapshot item xuất kho.
         *
         * `unit_price` là giá bán phục vụ doanh thu,
         * còn giá vốn thực tế sẽ do `InventoryLedgerService` tính khi confirm.
         */
        $payloads = [];
        $subtotal = 0;

        foreach ($items as $item) {
            // Nếu frontend không truyền giá bán thì lấy mặc định từ product, nhưng vẫn snapshot lại vào item.
            /** @var Product $product */
            $product = Product::query()->where('business_id', $businessId)->findOrFail($item['product_id']);
            $quantity = (float) $item['quantity'];
            $unitPrice = (float) ($item['unit_price'] ?? $product->sale_price);

            $payloads[] = [
                'product_id' => $product->id,
                'product_sku' => $product->sku,
                'product_name' => $product->name,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $quantity * $unitPrice,
            ];

            $subtotal += $quantity * $unitPrice;
        }

        return [$payloads, $subtotal];
    }

    /**
     * Đổi trạng thái phiếu xuất kho.
     *
     * @param  int  $id
     * @param  array<string, mixed>  $data
     * @param  string  $status
     * @return Model
     *
     * `confirm()` và `cancel()` đều đi qua đây.
     * Sau mỗi lần đổi trạng thái, ledger sẽ được sync lại từ đầu.
     */
    protected function transitionStatus(int $id, array $data, string $status): Model
    {
        // Trạng thái của chứng từ xuất kho tác động trực tiếp đến tồn hiện tại.
        $businessId = $this->resolveBusinessId($data);

        return DB::transaction(function () use ($businessId, $id, $status) {
            // Khi trạng thái đổi, movement cũ sẽ bị xóa hoặc tạo lại theo trạng thái mới.
            $stockOut = $this->stockOutRepository->findForBusiness($businessId, $id, ['items.product']);
            $this->stockOutRepository->updateRecord($stockOut, ['status' => $status]);
            $stockOut = $this->stockOutRepository->findForBusiness($businessId, $stockOut->id, ['items.product']);
            $this->inventoryLedgerService->syncStockOut($stockOut);

            return $this->stockOutRepository->findForBusiness($businessId, $stockOut->id, $this->with);
        });
    }
}
