<?php

namespace App\Http\Requests;

class InventoryIndexRequest extends BaseBusinessRequest
{
    public function rules(): array
    {
        return [
            'business_id' => $this->businessRules(),
            'warehouse_id' => ['nullable', 'integer'],
            'product_id' => ['nullable', 'integer'],
            'product_name' => ['nullable', 'string'],
            'sku' => ['nullable', 'string'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort_by' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'in:asc,desc'],
        ];
    }
}
