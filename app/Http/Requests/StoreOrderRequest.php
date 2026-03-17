<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class StoreOrderRequest extends BaseBusinessRequest
{
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
