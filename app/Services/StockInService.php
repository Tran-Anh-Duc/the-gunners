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

    public function create(array $data): Model
    {
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

            $stockIn = $this->stockInRepository->findForBusiness($businessId, $stockIn->id, ['items.product']);
            $this->inventoryLedgerService->syncStockIn($stockIn);

            return $this->stockInRepository->findForBusiness($businessId, $stockIn->id, $this->with);
        });
    }

    public function update(int $id, array $data): Model
    {
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

    protected function buildItems(int $businessId, array $items): array
    {
        $payloads = [];
        $subtotal = 0;

        foreach ($items as $item) {
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

    protected function transitionStatus(int $id, array $data, string $status): Model
    {
        $businessId = $this->resolveBusinessId($data);

        return DB::transaction(function () use ($businessId, $id, $status) {
            $stockIn = $this->stockInRepository->findForBusiness($businessId, $id, ['items.product']);
            $this->stockInRepository->updateRecord($stockIn, ['status' => $status]);
            $stockIn = $this->stockInRepository->findForBusiness($businessId, $stockIn->id, ['items.product']);
            $this->inventoryLedgerService->syncStockIn($stockIn);

            return $this->stockInRepository->findForBusiness($businessId, $stockIn->id, $this->with);
        });
    }
}
