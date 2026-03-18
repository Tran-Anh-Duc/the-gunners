<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

/**
 * Validate tạo phiếu thu/chi.
 *
 * Request chỉ kiểm tra format và enum cơ bản.
 * Việc khóa ngoại có cùng business hay không sẽ được service kiểm tra lại.
 */
class StorePaymentRequest extends BaseBusinessRequest
{
    /**
     * Rule tạo payment.
     *
     * Giải thích field chính:
     * - `direction`: `in` là thu tiền, `out` là chi tiền
     * - `method`: cách thanh toán
     * - `status`: trạng thái thanh toán hiện tại
     * - `order_id`/`stock_in_id`: document liên quan nếu có
     */
    public function rules(): array
    {
        $businessId = $this->integer('business_id');

        return [
            'business_id' => $this->businessRules(),
            'order_id' => ['nullable', 'integer', Rule::exists('orders', 'id')],
            'stock_in_id' => ['nullable', 'integer', Rule::exists('stock_in', 'id')],
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')],
            'supplier_id' => ['nullable', 'integer', Rule::exists('suppliers', 'id')],
            'payment_no' => ['nullable', 'string', 'max:50', Rule::unique('payments', 'payment_no')->where(fn ($query) => $query->where('business_id', $businessId))],
            'direction' => ['nullable', 'string', Rule::in(['in', 'out'])],
            'method' => ['nullable', 'string', Rule::in(['cash', 'bank_transfer', 'cod', 'e_wallet'])],
            'status' => ['nullable', 'string', Rule::in(['pending', 'paid', 'failed', 'cancelled'])],
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_date' => ['nullable', 'date'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string'],
        ];
    }
}
