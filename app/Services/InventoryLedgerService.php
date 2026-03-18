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
    /**
     * Service trung tâm của bài toán tồn kho.
     *
     * Quy ước hiện tại:
     * - `inventory_movements` là nguồn sự thật;
     * - `current_stocks` là read model để truy vấn nhanh;
     * - mỗi khi chứng từ kho thay đổi, movement cũ sẽ bị xóa và dựng lại từ document.
     *
     * Cách làm này đơn giản, dễ bảo trì và rất phù hợp cho một MVP thiên về CRUD.
     */
    /**
     * Đồng bộ ledger và current stock cho một chứng từ nhập kho.
     *
     * @param  StockIn  $stockIn  Header nhập kho đã có items. Method sẽ tự load thêm
     *                            `items.product` nếu relation chưa sẵn sàng.
     *
     * Luồng xử lý:
     * 1. Xác định các cặp kho - sản phẩm từng bị document này tác động.
     * 2. Xóa toàn bộ movement cũ của document.
     * 3. Nếu document ở trạng thái `confirmed`, tạo lại movement mới.
     * 4. Tính lại `current_stocks` cho toàn bộ cặp dữ liệu bị ảnh hưởng.
     *
     * Ví dụ:
     * - `SI-0001` nhập 10 sản phẩm A vào kho 1;
     * - method này sẽ tạo một movement có `quantity_change = +10`;
     * - sau đó tồn hiện tại của cặp (kho 1, sản phẩm A) được tính lại.
     */
    public function syncStockIn(StockIn $stockIn): void
    {
        // Xóa movement cũ rồi dựng lại từ chứng từ hiện tại để ledger luôn có thể rebuild chính xác.
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

    /**
     * Đồng bộ ledger cho chứng từ xuất kho.
     *
     * @param  StockOut  $stockOut  Header xuất kho cần đồng bộ.
     *
     * Điểm cần chú ý:
     * - Movement tạo ra là số âm.
     * - `unit_cost` ghi lúc đầu chỉ là tạm thời; giá vốn thực tế sẽ được
     *   `recalculateCurrentStocks()` chốt lại theo moving average.
     * - `$rejectNegative = true` dùng để chặn việc xác nhận chứng từ làm tồn kho âm.
     */
    public function syncStockOut(StockOut $stockOut): void
    {
        /**
         * Chứng từ xuất kho tạo movement âm.
         *
         * Giá vốn không khóa cứng trên item xuất kho,
         * mà được tính lại khi rebuild dựa trên giá vốn bình quân hiện có.
         */
        // Stock out là movement âm; sau đó current stock sẽ được tính lại từ ledger.
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

    /**
     * Đồng bộ ledger cho chứng từ kiểm kho hoặc điều chỉnh tồn.
     *
     * @param  StockAdjustment  $stockAdjustment  Document kiểm kho đã co items.
     *
     * Logic chính:
     * - `difference_qty > 0`: thêm tồn (`adjustment_in`)
     * - `difference_qty < 0`: giảm tồn (`adjustment_out`)
     * - `difference_qty = 0`: không tạo movement
     *
     * Ví dụ:
     * - hệ thống đang ghi nhận kho có 10 sản phẩm A;
     * - kiểm kho thực tế đếm được 8;
     * - `difference_qty = -2` nên tạo movement `adjustment_out` với `quantity_change = -2`.
     */
    public function syncStockAdjustment(StockAdjustment $stockAdjustment): void
    {
        /**
         * Adjustment là nghiệp vụ chốt lại tồn thực tế.
         *
         * `difference_qty > 0` nghĩa là hàng thực tế nhiều hơn hệ thống,
         * còn `difference_qty < 0` nghĩa là phải giảm tồn để khớp số kiểm kê.
         */
        // Adjustment chỉ tạo movement nếu thực sự có chênh lệch.
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

    /**
     * Lấy danh sách cặp `warehouse_id:product_id` từng bị document cũ tác động.
     *
     * @param  string  $sourceType  Loại document trong ledger, ví dụ `stock_in`
     * @param  int  $sourceId  ID của document gốc
     * @return Collection<int, string>
     *
     * Collection trả về giúp hệ thống nhớ rằng sau khi xóa movement cũ,
     * cần rebuild tồn cho những cặp kho - sản phẩm nào.
     */
    protected function existingAffectedKeys(string $sourceType, int $sourceId): Collection
    {
        // Lưu lại danh sách khóa bị ảnh hưởng trước khi xóa movement để còn biết phạm vi cần tính lại.
        return collect(
            InventoryMovement::query()
                ->where('source_type', $sourceType)
                ->where('source_id', $sourceId)
                ->get(['warehouse_id', 'product_id'])
                ->map(fn ($row) => $this->movementKey((int) $row->warehouse_id, (int) $row->product_id))
                ->all()
        );
    }

    /**
     * Tính lại bảng `current_stocks` từ inventory ledger.
     *
     * @param  int  $businessId  Business đang được rebuild tồn kho
     * @param  Collection<int, string>  $affectedKeys  Danh sách key dạng `warehouse_id:product_id`
     * @param  bool  $rejectNegative  Nếu `true` sẽ ném exception khi confirm chứng từ làm tồn kho âm
     *
     * Đây là hàm quan trọng nhất của bài toán tồn kho hiện tại.
     *
     * Cách tính:
     * - đọc ledger theo thứ tự `movement_date`, rồi tie-break bằng `id`;
     * - giữ hai biến chạy là `runningQty` và `runningValue`;
     * - với movement nhập thì cộng thêm số lượng và giá trị;
     * - với movement xuất thì lấy giá vốn bình quân hiện tại rồi trừ theo lượng xuất.
     *
     * Ví dụ moving average:
     * - nhập 10 cái giá 10.000 => qty 10, value 100.000
     * - nhập 10 cái giá 20.000 => qty 20, value 300.000
     * - Avg = 15.000
     * - xuất 4 cái => value giảm 60.000, qty còn 16, value còn 240.000
     */
    protected function recalculateCurrentStocks(int $businessId, Collection $affectedKeys, bool $rejectNegative = false): void
    {
        /**
         * Rebuild current stock từ đầu cho từng cặp warehouse-product bị ảnh hưởng.
         *
         * Ý tưởng:
         * - đọc toàn bộ movement theo thứ tự thời gian;
         * - cập nhật liên tục `runningQty` và `runningValue`;
         * - sau cùng ghi kết quả tổng hợp ra `current_stocks`.
         */
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
                // Mỗi movement sẽ làm thay đổi hai đại lượng lõi: số lượng tồn và giá trị tồn.
                $quantityChange = round((float) $movement->quantity_change, 3);
                $movementType = $movement->movement_type;

                if (in_array($movementType, ['stock_in', 'adjustment_in', 'opening'], true)) {
                    // Nhóm movement "vào kho": cộng cả số lượng lẫn giá trị tồn.
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
                    // Nhóm movement "ra kho": lấy giá vốn bình quân hiện tại làm giá vốn xuất.
                    $avgUnitCost = $runningQty > 0 ? round($runningValue / $runningQty, 2) : 0.0;
                    $projectedQty = round($runningQty + $quantityChange, 3);

                    if ($rejectNegative && $projectedQty < 0) {
                        // Chặn ngay khi chứng từ làm tồn âm để bảo vệ tính nhất quán nghiệp vụ.
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

            // `current_stocks` luôn là ảnh chụp cuối cùng sau khi đã đọc hết ledger của một cặp kho - sản phẩm.
            $quantityOnHand = round($runningQty, 3);
            $stockValue = round(max($runningValue, 0), 2);

            if ($quantityOnHand <= 0) {
                // Hết tồn thì xóa dòng tổng hợp để bảng current stock gọn và truy vấn nhanh hơn.
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

    /**
     * Tạo key chuỗi cho cặp kho - sản phẩm.
     *
     * @param  int  $warehouseId
     * @param  int  $productId
     * @return string
     *
     * Ví dụ: `1:25` nghĩa là product 25 tại kho 1.
     * Key này đủ đơn giản để:
     * - gom unique collection trước khi rebuild;
     * - tránh phải tạo object phức tạp cho nhu cầu MVP.
     */
    protected function movementKey(int $warehouseId, int $productId): string
    {
        // Key string đơn gian để unique cac cặp warehouse-product cần recalc.
        return $warehouseId.':'.$productId;
    }
}
