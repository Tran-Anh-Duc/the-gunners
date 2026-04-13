<?php

namespace App\Http\Requests;

use App\Models\WarehouseDocument;
use Illuminate\Validation\Rule;

class WarehouseDocumentListRequest extends BaseBusinessRequest
{
    public function rules(): array
    {
        return [
            'business_id' => $this->businessRules(),
            'document_code' => ['nullable', 'string'],
            'document_type' => ['nullable', Rule::in([
                WarehouseDocument::TYPE_IMPORT,
                WarehouseDocument::TYPE_EXPORT,
            ])],
            'warehouse_id' => ['nullable', 'integer'],
            'document_date_from' => ['nullable', 'date'],
            'document_date_to' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in([
                WarehouseDocument::STATUS_DRAFT,
                WarehouseDocument::STATUS_CONFIRMED,
                WarehouseDocument::STATUS_CANCELLED,
            ])],
            'reference_code' => ['nullable', 'string'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort_by' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'in:asc,desc'],
        ];
    }
}
