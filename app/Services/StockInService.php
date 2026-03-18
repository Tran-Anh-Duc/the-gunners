<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockIn;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Repositories\StockInRepository;
use App\Support\BusinessContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StockInService extends BaseBusinessCrudService
{
    protected array $with = ['warehouse', 'supplier', 'items.product'];

    protected array $searchable = ['stock_in_no', 'status', 'stock_in_type', 'reference_no'];

    public function __construct(
        BusinessContext $businessContext,
        private readonly StockInRepository $stockInRepository,
        private readonly InventoryLedgerService $inventoryLedgerService,
    ) {
        parent::__construct($businessContext);
        $this->repository = $stockInRepository;
    }

    /**
     * Tạo phiếu nhập kho.
     *
     * @param  array<string, mixed>  $data
     * @return Model
     *
     * Ví dụ item đầu vào:
     * [
     *   ['product_id' => 5, 'quantity' => 10, 'unit_cost' => 12000]
     * ]
     *
     * Service sẽ:
     * - kiểm tra warehouse và supplier cùng business;
     * - dựng snapshot item nhập kho;
     * - tính subtotal và total_amount ở backend;
     * - tạo header, item và đồng bộ ledger/current stock.
     */
    public function create(array $data): Model
    {
        /**
         * Tạo chứng từ nhập kho và đồng bộ sang ledger.
         *
         * Nếu status là `draft` thì chưa phát sinh movement.
         * Nếu status là `confirmed` thì movement và current stock được sinh ngay.
         */
        $businessId = $this->resolveBusinessId($data);

        return DB::transaction(function () use ($businessId, $data) {
            $this->assertBelongsToBusiness(Warehouse::class, $businessId, (int) $data['warehouse_id'], 'warehouse_id');
            $this->assertBelongsToBusiness(Supplier::class, $businessId, $data['supplier_id'] ?? null, 'supplier_id');

            [$itemsPayload, $subtotal] = $this->buildItems($businessId, $data['items']);
            $discountAmount = (float) ($data['discount_amount'] ?? 0);

            $stockIn = $this->stockInRepository->createForBusiness($businessId, [
                'warehouse_id' => $data['warehouse_id'],
                'supplier_id' => $data['supplier_id'] ?? null,
                'created_by' => $this->currentUserId(),
                'stock_in_no' => $data['stock_in_no'] ?? $this->nextDocumentNumber(StockIn::class, $businessId, 'stock_in_no', 'SI'),
                'reference_no' => $data['reference_no'] ?? null,
                'stock_in_type' => $data['stock_in_type'] ?? 'purchase',
                'stock_in_date' => $data['stock_in_date'] ?? now(),
                'status' => $data['status'] ?? 'draft',
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'total_amount' => $subtotal - $discountAmount,
                'note' => $data['note'] ?? null,
            ]);

            $this->stockInRepository->replaceItems($stockIn, $businessId, $itemsPayload);

            // Ledger là nguồn sự thật, nên mọi thay đổi ở stock-in đều phải đồng bộ lại movement và tồn hiện tại.
            $stockIn = $this->stockInRepository->findForBusiness($businessId, $stockIn->id, ['items.product']);
            $this->inventoryLedgerService->syncStockIn($stockIn);

            return $this->stockInRepository->findForBusiness($businessId, $stockIn->id, $this->with);
        });
    }

    /**
     * Cập nhật phiếu nhập kho.
     *
     * @param  int  $id
     * @param  array<string, mixed>  $data
     * @return Model
     *
     * Nếu request co `items`, toan bo items se được thay the.
     * Sau khi update xong, ledger luôn được rebuild lại.
     */
    public function update(int $id, array $data): Model
    {
        // Mỗi lần sửa chứng từ nhập kho đều phải rebuild lại movement liên quan để số tồn không lệch.
        $businessId = $this->resolveBusinessId($data);

        return DB::transaction(function () use ($businessId, $id, $data) {
            /** @var StockIn $stockIn */
            $stockIn = $this->stockInRepository->findForBusiness($businessId, $id, ['items.product']);

            $warehouseId = (int) ($data['warehouse_id'] ?? $stockIn->warehouse_id);
            $supplierId = $data['supplier_id'] ?? $stockIn->supplier_id;

            $this->assertBelongsToBusiness(Warehouse::class, $businessId, $warehouseId, 'warehouse_id');
            $this->assertBelongsToBusiness(Supplier::class, $businessId, $supplierId, 'supplier_id');

            $itemsData = $data['items'] ?? $stockIn->items->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_cost' => $item->unit_cost,
                ];
            })->all();

            [$itemsPayload, $subtotal] = $this->buildItems($businessId, $itemsData);
            $discountAmount = (float) ($data['discount_amount'] ?? $stockIn->discount_amount);

            $this->stockInRepository->updateRecord($stockIn, [
                'warehouse_id' => $warehouseId,
                'supplier_id' => $supplierId,
                'stock_in_no' => $data['stock_in_no'] ?? $stockIn->stock_in_no,
                'reference_no' => $data['reference_no'] ?? $stockIn->reference_no,
                'stock_in_type' => $data['stock_in_type'] ?? $stockIn->stock_in_type,
                'stock_in_date' => $data['stock_in_date'] ?? $stockIn->stock_in_date,
                'status' => $data['status'] ?? $stockIn->status,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'total_amount' => $subtotal - $discountAmount,
                'note' => $data['note'] ?? $stockIn->note,
            ]);

            if (array_key_exists('items', $data)) {
                $this->stockInRepository->replaceItems($stockIn, $businessId, $itemsPayload);
            }

            $stockIn = $this->stockInRepository->findForBusiness($businessId, $stockIn->id, ['items.product']);
            $this->inventoryLedgerService->syncStockIn($stockIn);

            return $this->stockInRepository->findForBusiness($businessId, $stockIn->id, $this->with);
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
     * Chuẩn hóa item nhập kho.
     *
     * @param  int  $businessId
     * @param  array<int, array<string, mixed>>  $items
     * @return array{0: array<int, array<string, mixed>>, 1: float}
     *
     * Giá trị trả về:
     * - index 0: danh sách payload item sẵn sàng lưu DB
     * - index 1: subtotal tính từ tổng `quantity * unit_cost`
     */
    protected function buildItems(int $businessId, array $items): array
    {
        /**
         * Chuẩn hóa item nhập kho.
         *
         * Nghiệp vụ nhập kho là nơi xác định giá vốn đầu vào,
         * nên `unit_cost` phải được lưu rõ trên từng item rồi đẩy xuống ledger.
         */
        $payloads = [];
        $subtotal = 0;

        foreach ($items as $item) {
            // Chụp snapshot SKU, tên và giá nhập tại thời điểm phát sinh để dữ liệu lịch sử không bị lệch.
            /** @var Product $product */
            $product = Product::query()->where('business_id', $businessId)->findOrFail($item['product_id']);
            $quantity = (float) $item['quantity'];
            $unitCost = (float) $item['unit_cost'];

            $payloads[] = [
                'product_id' => $product->id,
                'product_sku' => $product->sku,
                'product_name' => $product->name,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'line_total' => $quantity * $unitCost,
            ];

            $subtotal += $quantity * $unitCost;
        }

        return [$payloads, $subtotal];
    }

    /**
     * Đổi trạng thái chứng từ nhập kho.
     *
     * @param  int  $id
     * @param  array<string, mixed>  $data
     * @param  string  $status
     * @return Model
     *
     * `draft -> confirmed`: tạo movement nhập kho
     * `confirmed -> cancelled`: xóa movement của document và rebuild tồn
     */
    protected function transitionStatus(int $id, array $data, string $status): Model
    {
        // Confirm hoặc cancel làm thay đổi hiệu lực của chứng từ, nên bắt buộc phải đồng bộ lại tồn kho.
        $businessId = $this->resolveBusinessId($data);

        return DB::transaction(function () use ($businessId, $id, $status) {
            // Với ledger, confirm/cancel chính là thay đổi đầu vào nên phải sync lại từ đầu.
            $stockIn = $this->stockInRepository->findForBusiness($businessId, $id, ['items.product']);
            $this->stockInRepository->updateRecord($stockIn, ['status' => $status]);
            $stockIn = $this->stockInRepository->findForBusiness($businessId, $stockIn->id, ['items.product']);
            $this->inventoryLedgerService->syncStockIn($stockIn);

            return $this->stockInRepository->findForBusiness($businessId, $stockIn->id, $this->with);
        });
    }
}
