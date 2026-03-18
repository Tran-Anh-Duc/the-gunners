<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

/**
 * Validate tạo phiếu nhập kho.
 *
 * Stock-in là đầu vào của giá vốn,
 * nên `quantity` và `unit_cost` bắt buộc phải rõ ràng.
 */
class StoreStockInRequest extends BaseBusinessRequest
{
    /**
     * Rule tạo phiếu nhập kho.
     *
     * Giải thích field:
     * - `warehouse_id`: kho nhận hàng
     * - `supplier_id`: nhà cung cấp nếu là nhập mua
     * - `stock_in_type`: purchase/return/opening
     * - `items.*.quantity`: số lượng nhập
     * - `items.*.unit_cost`: giá nhập để tính giá vốn
     */
    public function rules(): array
    {
        $businessId = $this->integer('business_id');

        return [
            'business_id' => $this->businessRules(),
            'warehouse_id' => ['required', 'integer', Rule::exists('warehouses', 'id')],
            'supplier_id' => ['nullable', 'integer', Rule::exists('suppliers', 'id')],
            'stock_in_no' => ['nullable', 'string', 'max:50', Rule::unique('stock_in', 'stock_in_no')->where(fn ($query) => $query->where('business_id', $businessId))],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'stock_in_type' => ['nullable', 'string', Rule::in(['purchase', 'return', 'opening'])],
            'stock_in_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', Rule::in(['draft', 'confirmed', 'cancelled'])],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ];
    }
}
