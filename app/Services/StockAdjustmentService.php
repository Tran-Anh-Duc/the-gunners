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

    public function create(array $data): Model
    {
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

            $stockAdjustment = $this->stockAdjustmentRepository->findForBusiness($businessId, $stockAdjustment->id, ['items.product']);
            $this->inventoryLedgerService->syncStockAdjustment($stockAdjustment);

            return $this->stockAdjustmentRepository->findForBusiness($businessId, $stockAdjustment->id, $this->with);
        });
    }

    public function update(int $id, array $data): Model
    {
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

    protected function buildItems(int $businessId, int $warehouseId, array $items): array
    {
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

    protected function transitionStatus(int $id, array $data, string $status): Model
    {
        $businessId = $this->resolveBusinessId($data);

        return DB::transaction(function () use ($businessId, $id, $status) {
            $stockAdjustment = $this->stockAdjustmentRepository->findForBusiness($businessId, $id, ['items.product']);
            $this->stockAdjustmentRepository->updateRecord($stockAdjustment, ['status' => $status]);
            $stockAdjustment = $this->stockAdjustmentRepository->findForBusiness($businessId, $stockAdjustment->id, ['items.product']);
            $this->inventoryLedgerService->syncStockAdjustment($stockAdjustment);

            return $this->stockAdjustmentRepository->findForBusiness($businessId, $stockAdjustment->id, $this->with);
        });
    }
}
