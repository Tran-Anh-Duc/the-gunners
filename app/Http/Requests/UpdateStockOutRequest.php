<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateStockOutRequest extends BaseBusinessRequest
{
    public function rules(): array
    {
        $businessId = $this->integer('business_id');
        $id = (int) $this->route('id');

        return [
            'business_id' => $this->businessRules(),
            'warehouse_id' => ['sometimes', 'required', 'integer', Rule::exists('warehouses', 'id')],
            'order_id' => ['nullable', 'integer', Rule::exists('orders', 'id')],
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')],
            'stock_out_no' => ['nullable', 'string', 'max:50', Rule::unique('stock_out', 'stock_out_no')->ignore($id)->where(fn ($query) => $query->where('business_id', $businessId))],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'stock_out_type' => ['nullable', 'string', Rule::in(['sale', 'return', 'adjustment'])],
            'stock_out_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', Rule::in(['draft', 'confirmed', 'cancelled'])],
            'note' => ['nullable', 'string'],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.product_id' => ['required_with:items', 'integer', Rule::exists('products', 'id')],
            'items.*.quantity' => ['required_with:items', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
