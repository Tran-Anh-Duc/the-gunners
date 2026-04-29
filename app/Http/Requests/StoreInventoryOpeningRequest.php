<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class StoreInventoryOpeningRequest extends BaseBusinessRequest
{
    public function rules(): array
    {
        $businessId = $this->integer('business_id');
        $warehouseId = $this->integer('warehouse_id');

        return [
            'business_id' => $this->businessRules(),
            'warehouse_id' => [
                'required',
                'integer',
                Rule::exists('warehouses', 'id')->where(
                    fn ($query) => $query->where('business_id', $businessId)
                ),
            ],
            'product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where(
                    fn ($query) => $query->where('business_id', $businessId)
                ),
                Rule::unique('inventory_openings', 'product_id')->where(
                    fn ($query) => $query
                        ->where('business_id', $businessId)
                        ->where('warehouse_id', $warehouseId)
                ),
            ],
            'unit_id' => [
                'required',
                'integer',
                Rule::exists('units', 'id')->where(
                    fn ($query) => $query->where('business_id', $businessId)
                ),
            ],
            'opening_date' => ['required', 'date'],
            'quantity' => ['required', 'numeric', 'min:0'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string'],
            'product_name' => ['required', 'string'],
            'unit_name' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'warehouse_id.exists' => 'The selected value is invalid for the current business.',
            'product_id.exists' => 'The selected value is invalid for the current business.',
            'unit_id.exists' => 'The selected value is invalid for the current business.',
            'product_id.unique' => 'An opening stock already exists for this business, warehouse, and product.',
        ];
    }
}
