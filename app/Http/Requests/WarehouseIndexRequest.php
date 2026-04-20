<?php

namespace App\Http\Requests;

class WarehouseIndexRequest extends BaseBusinessRequest
{
    public function rules(): array
    {
        return [
            'business_id' => $this->businessRules(),
            'code' => ['nullable', 'string'],
            'name' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort_by' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'in:asc,desc'],
            'is_option' => ['nullable', 'boolean'],
        ];
    }
}
