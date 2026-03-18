<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

/**
 * Validate cập nhật phiếu nhập kho.
 *
 * Hỗ trợ sua item va header trước khi confirm document.
 */
class UpdateStockInRequest extends BaseBusinessRequest
{
    public function rules(): array
    {
        $businessId = $this->integer('business_id');
        $id = (int) $this->route('id');

        return [
            'business_id' => $this->businessRules(),
            'warehouse_id' => ['sometimes', 'required', 'integer', Rule::exists('warehouses', 'id')],
            'supplier_id' => ['nullable', 'integer', Rule::exists('suppliers', 'id')],
            'stock_in_no' => ['nullable', 'string', 'max:50', Rule::unique('stock_in', 'stock_in_no')->ignore($id)->where(fn ($query) => $query->where('business_id', $businessId))],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'stock_in_type' => ['nullable', 'string', Rule::in(['purchase', 'return', 'opening'])],
            'stock_in_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', Rule::in(['draft', 'confirmed', 'cancelled'])],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string'],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.product_id' => ['required_with:items', 'integer', Rule::exists('products', 'id')],
            'items.*.quantity' => ['required_with:items', 'numeric', 'gt:0'],
            'items.*.unit_cost' => ['required_with:items', 'numeric', 'min:0'],
        ];
    }
}
