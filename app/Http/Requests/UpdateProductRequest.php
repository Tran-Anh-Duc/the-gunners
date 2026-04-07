<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

/**
 * Validate cập nhật san phâm.
 *
 * product_type của MVP hien chỉ hỗ trợ simple,
 * để tránh dua variant vao qua som.
 */
class UpdateProductRequest extends BaseBusinessRequest
{
    public function rules(): array
    {
        return [
            'business_id' => $this->businessRules(),
            'unit_id' => ['sometimes', 'required', 'integer', Rule::exists('units', 'id')],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'sku' => ['prohibited'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'barcode' => ['nullable', 'string', 'max:100'],
            'product_type' => ['nullable', 'string', Rule::in(['simple'])],
            'track_inventory' => ['nullable', 'boolean'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive'])],
            'description' => ['nullable', 'string'],
        ];
    }
}
