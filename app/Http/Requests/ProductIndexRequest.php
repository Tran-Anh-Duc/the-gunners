<?php

namespace App\Http\Requests;

class ProductIndexRequest extends BaseBusinessRequest
{
    public function rules(): array
    {
        return [
            'business_id' => $this->businessRules(),
            'sku' => ['nullable', 'string'],
            'name' => ['nullable', 'string'],
            'barcode' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'category_id' => ['nullable', 'integer'],
            'unit_id' => ['nullable', 'integer'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort_by' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'in:asc,desc'],
        ];
    }
}
