<?php

namespace App\Services;

use App\Models\CurrentStock;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\Warehouse;
use App\Repositories\StockAdjustmentRepository;
use App\Support\BusinessContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StockAdjustmentService extends BaseBusinessCrudService
{
    protected array $with = ['warehouse', 'items.product'];

    protected array $searchable = ['adjustment_no', 'status', 'reason'];

    public function __construct(
        BusinessContext $businessContext,
        private readonly StockAdjustmentRepository $stockAdjustmentRepository,
        private readonly InventoryLedgerService $inventoryLedgerService,
    ) {
        parent::__construct($businessContext);
        $this->repository = $stockAdjustmentRepository;
    }

    /**
     * Tạo chứng từ kiểm kho.
     *
     * @param  array<string, mixed>  $data
     * @return Model
     *
     * Item đầu vào thường có dạng:
     * [
     *   [
     *     'product_id' => 10,
     *     'counted_qty' => 8,
     *     'expected_qty' => 10
     *   ]
     * ]
     *
     * Sau khi tạo xong header và item, service sẽ sync ledger nếu document đã `confirmed`.
     */
    public function create(array $data): Model
    {
        /**
         * Tạo chứng từ kiểm kho/điều chỉnh tồn.
         *
         * `expected_qty` được lấy từ current stock nếu frontend không gửi,
         * `counted_qty` là số đếm thực tế và `difference_qty` được tính từ chênh lệch hai giá trị này.
         */
        $businessId = $this->resolveBusinessId($data);

        return DB::transaction(function () use ($businessId, $data) {
            $warehouseId = (int) $data['warehouse_id'];
            $this->assertBelongsToBusiness(Warehouse::class, $businessId, $warehouseId, 'warehouse_id');

            [$itemsPayload] = $this->buildItems($businessId, $warehouseId, $data['items']);

            $stockAdjustment = $this->stockAdjustmentRepository->createForBusiness($businessId, [
                'warehouse_id' => $warehouseId,
                'created_by' => $this->currentUserId(),
                'adjustment_no' => $data['adjustment_no'] ?? $this->nextDocumentNumber(StockAdjustment::class, $businessId, 'adjustment_no', 'ADJ'),
                'adjustment_date' => $data['adjustment_date'] ?? now(),
                'reason' => $data['reason'] ?? null,
                'status' => $data['status'] ?? 'draft',
                'note' => $data['note'] ?? null,
            ]);

            $this->stockAdjustmentRepository->replaceItems($stockAdjustment, $businessId, $itemsPayload);

            // Adjustment là nghiệp vụ điều chỉnh chênh lệch kiểm kho nên phải sync ledger ngay sau khi lưu.
            $stockAdjustment = $this->stockAdjustmentRepository->findForBusiness($businessId, $stockAdjustment->id, ['items.product']);
            $this->inventoryLedgerService->syncStockAdjustment($stockAdjustment);

            return $this->stockAdjustmentRepository->findForBusiness($businessId, $stockAdjustment->id, $this->with);
        });
    }

    /**
     * Cập nhật document kiểm kho.
     *
     * @param  int  $id
     * @param  array<string, mixed>  $data
     * @return Model
     *
     * Nếu request gửi `items`, toàn bộ item sẽ được build lại và replace lại.
     * Cách này đơn giản hơn cho MVP và bảo đảm ledger luôn đồng bộ.
     */
    public function update(int $id, array $data): Model
    {
        // Mỗi lần sửa adjustment đều phải rebuild lại item và ledger liên quan.
        $businessId = $this->resolveBusinessId($data);

        return DB::transaction(function () use ($businessId, $id, $data) {
            /** @var StockAdjustment $stockAdjustment */
            $stockAdjustment = $this->stockAdjustmentRepository->findForBusiness($businessId, $id, ['items.product']);
            $warehouseId = (int) ($data['warehouse_id'] ?? $stockAdjustment->warehouse_id);

            $this->assertBelongsToBusiness(Warehouse::class, $businessId, $warehouseId, 'warehouse_id');

            $itemsData = $data['items'] ?? $stockAdjustment->items->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'counted_qty' => $item->counted_qty,
                    'expected_qty' => $item->expected_qty,
                    'unit_cost' => $item->unit_cost,
                    'note' => $item->note,
                ];
            })->all();

            [$itemsPayload] = $this->buildItems($businessId, $warehouseId, $itemsData);

            $this->stockAdjustmentRepository->updateRecord($stockAdjustment, [
                'warehouse_id' => $warehouseId,
                'adjustment_no' => $data['adjustment_no'] ?? $stockAdjustment->adjustment_no,
                'adjustment_date' => $data['adjustment_date'] ?? $stockAdjustment->adjustment_date,
                'reason' => $data['reason'] ?? $stockAdjustment->reason,
                'status' => $data['status'] ?? $stockAdjustment->status,
                'note' => $data['note'] ?? $stockAdjustment->note,
            ]);

            if (array_key_exists('items', $data)) {
                $this->stockAdjustmentRepository->replaceItems($stockAdjustment, $businessId, $itemsPayload);
            }

            $stockAdjustment = $this->stockAdjustmentRepository->findForBusiness($businessId, $stockAdjustment->id, ['items.product']);
            $this->inventoryLedgerService->syncStockAdjustment($stockAdjustment);

            return $this->stockAdjustmentRepository->findForBusiness($businessId, $stockAdjustment->id, $this->with);
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
     * Chuyển item input thành snapshot để lưu DB.
     *
     * @param  int  $businessId
     * @param  int  $warehouseId
     * @param  array<int, array<string, mixed>>  $items
     * @return array{0: array<int, array<string, mixed>>}
     *
     * Cách tính:
     * - `expected_qty`: lấy từ request nếu có, nếu không thì đọc từ `current_stocks`;
     * - `counted_qty`: số lượng kiểm thực tế do người dùng nhập;
     * - `difference_qty = counted_qty - expected_qty`;
     * - `unit_cost`: ưu tiên request, rồi `avg_unit_cost` hiện tại, rồi `cost_price` của product.
     */
    protected function buildItems(int $businessId, int $warehouseId, array $items): array
    {
        /**
         * Chuẩn hóa item kiểm kho thành snapshot lưu DB.
         *
         * `expected_qty`: tồn hệ thống;
         * `counted_qty`: tồn đếm thực tế;
         * `difference_qty`: lượng chênh lệch cần đưa vào ledger.
         */
        $payloads = [];

        foreach ($items as $item) {
            /** @var Product $product */
            $product = Product::query()
                ->where('business_id', $businessId)
                ->findOrFail($item['product_id']);

            $currentStock = CurrentStock::query()
                ->where('business_id', $businessId)
                ->where('warehouse_id', $warehouseId)
                ->where('product_id', $product->id)
                ->first();

            // Nếu frontend không truyền `expected_qty` thì lấy theo tồn hiện tại của kho - sản phẩm.
            $expectedQty = array_key_exists('expected_qty', $item)
                ? (float) $item['expected_qty']
                : (float) ($currentStock?->quantity_on_hand ?? 0);
            $countedQty = (float) $item['counted_qty'];
            $differenceQty = $countedQty - $expectedQty;
            $unitCost = (float) ($item['unit_cost'] ?? $currentStock?->avg_unit_cost ?? $product->cost_price);

            $payloads[] = [
                'product_id' => $product->id,
                'product_sku' => $product->sku,
                'product_name' => $product->name,
                'expected_qty' => $expectedQty,
                'counted_qty' => $countedQty,
                'difference_qty' => $differenceQty,
                'unit_cost' => $unitCost,
                'line_total' => $differenceQty * $unitCost,
                'note' => $item['note'] ?? null,
            ];
        }

        return [$payloads];
    }

    /**
     * Đổi trạng thái adjustment và rebuild ledger.
     *
     * @param  int  $id
     * @param  array<string, mixed>  $data
     * @param  string  $status
     * @return Model
     *
     * Vì adjustment là document tác động trực tiếp vào tồn,
     * nên confirm hoặc cancel đều phải sync lại ledger/current stock.
     */
    protected function transitionStatus(int $id, array $data, string $status): Model
    {
        // Confirm hoặc cancel adjustment sẽ thay đổi đầu vào của tồn kho nên bắt buộc sync lại ledger.
        $businessId = $this->resolveBusinessId($data);

        return DB::transaction(function () use ($businessId, $id, $status) {
            // Adjustment là đầu vào trực tiếp của ledger nên đổi status là phải tính lại toàn bộ.
            $stockAdjustment = $this->stockAdjustmentRepository->findForBusiness($businessId, $id, ['items.product']);
            $this->stockAdjustmentRepository->updateRecord($stockAdjustment, ['status' => $status]);
            $stockAdjustment = $this->stockAdjustmentRepository->findForBusiness($businessId, $stockAdjustment->id, ['items.product']);
            $this->inventoryLedgerService->syncStockAdjustment($stockAdjustment);

            return $this->stockAdjustmentRepository->findForBusiness($businessId, $stockAdjustment->id, $this->with);
        });
    }
}
