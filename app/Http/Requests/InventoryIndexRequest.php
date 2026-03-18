<?php

namespace App\Http\Requests;

/**
 * Request đọc tồn kho hiện tại.
 *
 * Đây là request list có thêm filter nghiệp vụ:
 * - warehouse_id
 * - product_id
 * - product_name
 * - sku
 */
class InventoryIndexRequest extends BaseBusinessRequest
{
    /**
     * Rule cho màn hình xem tồn kho hiện tại.
     */
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
