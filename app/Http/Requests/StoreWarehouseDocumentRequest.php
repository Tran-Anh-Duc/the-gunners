<?php

namespace App\Http\Requests;

use App\Models\WarehouseDocument;
use Illuminate\Validation\Rule;

class StoreWarehouseDocumentRequest extends BaseBusinessRequest
{
    public function rules(): array
    {
        $businessId = $this->integer('business_id');

        return [
            'business_id' => $this->businessRules(),
            'document_code' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('warehouse_documents', 'document_code')->where(
                    fn ($query) => $query->where('business_id', $businessId)
                ),
            ],
            'document_type' => ['required', Rule::in([
                WarehouseDocument::TYPE_IMPORT,
                WarehouseDocument::TYPE_EXPORT,
            ])],
            'warehouse_id' => ['required', 'integer', Rule::exists('warehouses', 'id')],
            'document_date' => ['required', 'date'],
            'status' => ['nullable', Rule::in([
                WarehouseDocument::STATUS_DRAFT,
                WarehouseDocument::STATUS_CONFIRMED,
                WarehouseDocument::STATUS_CANCELLED,
            ])],
            'reference_code' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
            'approved_by' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'approved_at' => ['nullable', 'date'],
            'details' => ['nullable', 'array'],
            'details.*.product_id' => ['required_with:details', 'integer', Rule::exists('products', 'id')],
            'details.*.product_name' => ['nullable', 'string', 'max:255'],
            'details.*.unit_id' => ['required_with:details', 'integer', Rule::exists('units', 'id')],
            'details.*.unit_name' => ['nullable', 'string', 'max:255'],
            'details.*.quantity' => ['required_with:details', 'numeric', 'min:0'],
            'details.*.unit_price' => ['required_with:details', 'numeric', 'min:0'],
            'details.*.subtotal' => ['nullable', 'numeric', 'min:0'],
            'details.*.tax_rate' => ['nullable', 'numeric', 'min:0'],
            'details.*.tax_price' => ['nullable', 'numeric', 'min:0'],
            'details.*.total_price' => ['nullable', 'numeric', 'min:0'],
            'details.*.note' => ['nullable', 'string'],
        ];
    }
}
