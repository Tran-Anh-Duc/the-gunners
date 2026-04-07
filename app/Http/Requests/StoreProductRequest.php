<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

/**
 * Validate tạo san phâm.
 *
 * SKU được unique theo business, không unique toan hệ thống,
 * vì mỗi shop có thể co quy uoc ma hang rieng.
 */
class StoreProductRequest extends BaseBusinessRequest
{
    /**
     * Rule tạo product.
     *
     * Giai thich field:
     * - `unit_id`: đơn vì tinh của sản phẩm
     * - `sku`: ma hang unique trong business
     * - `track_inventory`: có thểo đổi tồn kho hay không
     * - `cost_price`: giá von mặc định
     * - `sale_price`: giá ban mặc định
     */
    public function rules(): array
    {
        $businessId = $this->integer('business_id');

        return [
            'business_id' => $this->businessRules(),
            'unit_id' => ['required', 'integer', Rule::exists('units', 'id')],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'sku' => ['nullable', 'string', 'min:1', 'max:100', Rule::unique('products', 'sku')->where(fn ($query) => $query->where('business_id', $businessId))],
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
