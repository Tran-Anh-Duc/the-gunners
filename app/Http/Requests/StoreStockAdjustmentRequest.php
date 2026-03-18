<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

/**
 * Validate tạo chứng từ kiểm kho/điều chỉnh tồn.
 *
 * Adjustment item cần `counted_qty` để tính chênh lệch,
 * còn `expected_qty` có thể để hệ thống tự suy ra từ current stock.
 */
class StoreStockAdjustmentRequest extends BaseBusinessRequest
{
    /**
     * Rule validate đầu vào cho nghiệp vụ kiểm kho.
     *
     * Service sẽ tính tiếp `difference_qty` và `line_total`
     * sau khi request đã qua bước xác thực dữ liệu cơ bản.
     */
    public function rules(): array
    {
        $businessId = $this->integer('business_id');

        return [
            'business_id' => $this->businessRules(),
            'warehouse_id' => ['required', 'integer', Rule::exists('warehouses', 'id')],
            'adjustment_no' => ['nullable', 'string', 'max:50', Rule::unique('stock_adjustments', 'adjustment_no')->where(fn ($query) => $query->where('business_id', $businessId))],
            'adjustment_date' => ['nullable', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', Rule::in(['draft', 'confirmed', 'cancelled'])],
            'note' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'items.*.expected_qty' => ['nullable', 'numeric', 'min:0'],
            'items.*.counted_qty' => ['required', 'numeric', 'min:0'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
