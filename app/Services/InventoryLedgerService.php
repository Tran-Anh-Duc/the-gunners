<?php

namespace App\Services;

use App\Models\CurrentStock;
use App\Models\InventoryMovement;
use App\Models\StockAdjustment;
use App\Models\StockIn;
use App\Models\StockOut;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class InventoryLedgerService
{
    public function syncStockIn(StockIn $stockIn): void
    {
        $stockIn->loadMissing('items.product');
        $affectedKeys = $this->existingAffectedKeys('stock_in', $stockIn->id);

        InventoryMovement::query()
            ->where('source_type', 'stock_in')
            ->where('source_id', $stockIn->id)
            ->delete();

        if ($stockIn->status === 'confirmed') {
            foreach ($stockIn->items as $item) {
                $affectedKeys->push($this->movementKey($stockIn->warehouse_id, $item->product_id));

                InventoryMovement::query()->create([
                    'business_id' => $stockIn->business_id,
                    'warehouse_id' => $stockIn->warehouse_id,
                    'product_id' => $item->product_id,
                    'movement_type' => 'stock_in',
                    'source_type' => 'stock_in',
                    'source_id' => $stockIn->id,
                    'source_code' => $stockIn->stock_in_no,
                    'quantity_change' => $item->quantity,
                    'unit_cost' => $item->unit_cost,
                    'total_cost' => $item->line_total,
                    'movement_date' => $stockIn->stock_in_date,
                    'note' => $stockIn->note,
                    'created_by' => $stockIn->created_by,
                ]);
            }
        }

        $this->recalculateCurrentStocks($stockIn->business_id, $affectedKeys);
    }

    public function syncStockOut(StockOut $stockOut): void
    {
        $stockOut->loadMissing('items.product');
        $affectedKeys = $this->existingAffectedKeys('stock_out', $stockOut->id);

        InventoryMovement::query()
            ->where('source_type', 'stock_out')
            ->where('source_id', $stockOut->id)
            ->delete();

        if ($stockOut->status === 'confirmed') {
            foreach ($stockOut->items as $item) {
                $affectedKeys->push($this->movementKey($stockOut->warehouse_id, $item->product_id));

                InventoryMovement::query()->create([
                    'business_id' => $stockOut->business_id,
                    'warehouse_id' => $stockOut->warehouse_id,
                    'product_id' => $item->product_id,
                    'movement_type' => 'stock_out',
                    'source_type' => 'stock_out',
                    'source_id' => $stockOut->id,
                    'source_code' => $stockOut->stock_out_no,
                    'quantity_change' => -1 * $item->quantity,
                    'unit_cost' => $item->product->cost_price,
                    'total_cost' => -1 * $item->quantity * $item->product->cost_price,
                    'movement_date' => $stockOut->stock_out_date,
                    'note' => $stockOut->note,
                    'created_by' => $stockOut->created_by,
                ]);
            }
        }

        $this->recalculateCurrentStocks($stockOut->business_id, $affectedKeys, true);
    }

    public function syncStockAdjustment(StockAdjustment $stockAdjustment): void
    {
        $stockAdjustment->loadMissing('items.product');
        $affectedKeys = $this->existingAffectedKeys('stock_adjustment', $stockAdjustment->id);

        InventoryMovement::query()
            ->where('source_type', 'stock_adjustment')
            ->where('source_id', $stockAdjustment->id)
            ->delete();

        if ($stockAdjustment->status === 'confirmed') {
            foreach ($stockAdjustment->items as $item) {
                $affectedKeys->push($this->movementKey($stockAdjustment->warehouse_id, $item->product_id));

                if ((float) $item->difference_qty === 0.0) {
                    continue;
                }

                InventoryMovement::query()->create([
                    'business_id' => $stockAdjustment->business_id,
                    'warehouse_id' => $stockAdjustment->warehouse_id,
                    'product_id' => $item->product_id,
                    'movement_type' => $item->difference_qty >= 0 ? 'adjustment_in' : 'adjustment_out',
                    'source_type' => 'stock_adjustment',
                    'source_id' => $stockAdjustment->id,
                    'source_code' => $stockAdjustment->adjustment_no,
                    'quantity_change' => $item->difference_qty,
                    'unit_cost' => $item->unit_cost,
                    'total_cost' => $item->line_total,
                    'movement_date' => $stockAdjustment->adjustment_date,
                    'note' => $item->note ?: $stockAdjustment->note,
                    'created_by' => $stockAdjustment->created_by,
                ]);
            }
        }

        $this->recalculateCurrentStocks($stockAdjustment->business_id, $affectedKeys);
    }

    protected function existingAffectedKeys(string $sourceType, int $sourceId): Collection
    {
        return collect(
            InventoryMovement::query()
                ->where('source_type', $sourceType)
                ->where('source_id', $sourceId)
                ->get(['warehouse_id', 'product_id'])
                ->map(fn ($row) => $this->movementKey((int) $row->warehouse_id, (int) $row->product_id))
                ->all()
        );
    }

    protected function recalculateCurrentStocks(int $businessId, Collection $affectedKeys, bool $rejectNegative = false): void
    {
        $affectedKeys = $affectedKeys->unique()->values();

        foreach ($affectedKeys as $key) {
            [$warehouseId, $productId] = explode(':', $key);
            $runningQty = 0.0;
            $runningValue = 0.0;
            $lastMovementAt = null;

            $movements = InventoryMovement::query()
                ->where('business_id', $businessId)
                ->where('warehouse_id', (int) $warehouseId)
                ->where('product_id', (int) $productId)
                ->orderBy('movement_date')
                ->orderBy('id')
                ->get();

            foreach ($movements as $movement) {
                $quantityChange = round((float) $movement->quantity_change, 3);
                $movementType = $movement->movement_type;

                if (in_array($movementType, ['stock_in', 'adjustment_in', 'opening'], true)) {
                    $unitCost = round((float) $movement->unit_cost, 2);
                    $totalCost = round($quantityChange * $unitCost, 2);

                    if ((float) $movement->total_cost !== $totalCost) {
                        $movement->forceFill([
                            'unit_cost' => $unitCost,
                            'total_cost' => $totalCost,
                        ])->save();
                    }

                    $runningQty += $quantityChange;
                    $runningValue += $totalCost;
                } else {
                    $avgUnitCost = $runningQty > 0 ? round($runningValue / $runningQty, 2) : 0.0;
                    $projectedQty = round($runningQty + $quantityChange, 3);

                    if ($rejectNegative && $projectedQty < 0) {
                        throw ValidationException::withMessages([
                            'items' => 'Insufficient stock to confirm this document.',
                        ]);
                    }

                    $totalCost = round($quantityChange * $avgUnitCost, 2);

                    if ((float) $movement->unit_cost !== $avgUnitCost || (float) $movement->total_cost !== $totalCost) {
                        $movement->forceFill([
                            'unit_cost' => $avgUnitCost,
                            'total_cost' => $totalCost,
                        ])->save();
                    }

                    $runningQty = $projectedQty;
                    $runningValue += $totalCost;
                }

                $lastMovementAt = $movement->movement_date;
            }

            $quantityOnHand = round($runningQty, 3);
            $stockValue = round(max($runningValue, 0), 2);

            if ($quantityOnHand <= 0) {
                CurrentStock::query()
                    ->where('business_id', $businessId)
                    ->where('warehouse_id', (int) $warehouseId)
                    ->where('product_id', (int) $productId)
                    ->delete();
                continue;
            }

            CurrentStock::query()->updateOrCreate(
                [
                    'business_id' => $businessId,
                    'warehouse_id' => (int) $warehouseId,
                    'product_id' => (int) $productId,
                ],
                [
                    'quantity_on_hand' => $quantityOnHand,
                    'stock_value' => $stockValue,
                    'avg_unit_cost' => $quantityOnHand > 0 ? round($stockValue / $quantityOnHand, 2) : 0,
                    'last_movement_at' => $lastMovementAt,
                ]
            );
        }
    }

    protected function movementKey(int $warehouseId, int $productId): string
    {
        return $warehouseId.':'.$productId;
    }
}
