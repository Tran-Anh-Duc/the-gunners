<?php

namespace App\Http\Requests;

use App\Models\InventoryOpening;
use Illuminate\Validation\Rule;

class UpdateInventoryOpeningRequest extends BaseBusinessRequest
{
    public function rules(): array
    {
        $openingId = (int) $this->route('id');
        $opening = InventoryOpening::query()->find($openingId);

        $businessId = $this->integer('business_id') ?: (int) ($opening?->business_id ?? 0);
        $warehouseId = $this->integer('warehouse_id') ?: (int) ($opening?->warehouse_id ?? 0);

        return [
            'business_id' => $this->businessRules(),
            'warehouse_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('warehouses', 'id')->where(
                    fn ($query) => $query->where('business_id', $businessId)
                ),
            ],
            'product_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('products', 'id')->where(
                    fn ($query) => $query->where('business_id', $businessId)
                ),
                Rule::unique('inventory_openings', 'product_id')
                    ->ignore($openingId)
                    ->where(
                        fn ($query) => $query
                            ->where('business_id', $businessId)
                            ->where('warehouse_id', $warehouseId)
                    ),
            ],
            'unit_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('units', 'id')->where(
                    fn ($query) => $query->where('business_id', $businessId)
                ),
            ],
            'opening_date' => ['sometimes', 'required', 'date'],
            'quantity' => ['sometimes', 'required', 'numeric', 'min:0'],
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
