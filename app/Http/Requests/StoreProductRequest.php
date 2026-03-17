<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class StoreProductRequest extends BaseBusinessRequest
{
    public function rules(): array
    {
        $businessId = $this->integer('business_id');

        return [
            'business_id' => $this->businessRules(),
            'unit_id' => ['required', 'integer', Rule::exists('units', 'id')],
            'sku' => ['required', 'string', 'max:100', Rule::unique('products', 'sku')->where(fn ($query) => $query->where('business_id', $businessId))],
            'name' => ['required', 'string', 'max:255'],
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
