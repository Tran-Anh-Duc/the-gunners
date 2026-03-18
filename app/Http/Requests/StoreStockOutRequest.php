<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

/**
 * Validate tạo phiếu xuất kho.
 *
 * `quantity` là bắt buộc,
 * còn `unit_price` có thể để hệ thống lấy mặc định từ product.
 */
class StoreStockOutRequest extends BaseBusinessRequest
{
    /**
     * Rule tạo phiếu xuất kho.
     *
     * Giải thích field:
     * - `warehouse_id`: kho xuất
     * - `order_id`: đơn hàng liên quan nếu có
     * - `customer_id`: khách nhận hàng nếu có
     * - `items.*.quantity`: số lượng xuất
     * - `items.*.unit_price`: giá bán trên chứng từ xuất
     */
    public function rules(): array
    {
        $businessId = $this->integer('business_id');

        return [
            'business_id' => $this->businessRules(),
            'warehouse_id' => ['required', 'integer', Rule::exists('warehouses', 'id')],
            'order_id' => ['nullable', 'integer', Rule::exists('orders', 'id')],
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')],
            'stock_out_no' => ['nullable', 'string', 'max:50', Rule::unique('stock_out', 'stock_out_no')->where(fn ($query) => $query->where('business_id', $businessId))],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'stock_out_type' => ['nullable', 'string', Rule::in(['sale', 'return', 'adjustment'])],
            'stock_out_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', Rule::in(['draft', 'confirmed', 'cancelled'])],
            'note' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
