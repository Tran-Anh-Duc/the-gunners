<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

/**
 * Validate tạo đơn hàng.
 *
 * Giải thích field chính:
 * - `warehouse_id`: kho xuất dự kiến
 * - `customer_id`: khach mua, có thể null
 * - `order_no`: có thể do frontend gửi hoặc để service tự sinh
 * - `items.*.product_id`: sản phẩm được bán
 * - `items.*.quantity`: số lượng bán
 * - `items.*.unit_price`: giá bán, có thể để null để service lấy mặc định
 * - `items.*.discount_amount`: giảm giá theo dòng
 */
class StoreOrderRequest extends BaseBusinessRequest
{
    /**
     * Rule validate đầu vào cho nghiệp vụ tạo đơn hàng.
     *
     * Request chỉ kiểm tra format và enum cơ bản; business scope thật sự
     * của warehouse, customer và product sẽ được service kiểm tra thêm.
     */
    public function rules(): array
    {
        $businessId = $this->integer('business_id');

        return [
            'business_id' => $this->businessRules(),
            'warehouse_id' => ['required', 'integer', Rule::exists('warehouses', 'id')],
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')],
            'order_no' => ['nullable', 'string', 'max:50', Rule::unique('orders', 'order_no')->where(fn ($query) => $query->where('business_id', $businessId))],
            'order_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', Rule::in(['draft', 'confirmed', 'completed', 'cancelled'])],
            'payment_status' => ['nullable', 'string', Rule::in(['unpaid', 'partial', 'paid'])],
            'shipping_amount' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
