<?php

namespace App\Transformers;

use App\Models\InventoryOpening;
use League\Fractal\TransformerAbstract;

class InventoryOpeningTransformer extends TransformerAbstract
{
    protected array $defaultIncludes = [];

    protected array $availableIncludes = [];

    public function transform(InventoryOpening $entry): array
    {
        return [
            'id' => $entry->id,
            'business_id' => $entry->business_id,
            'warehouse_id' => $entry->warehouse_id,
            'warehouse' => $entry->relationLoaded('warehouse') && $entry->warehouse
                ? [
                    'id' => $entry->warehouse->id,
                    'code' => $entry->warehouse->code,
                    'name' => $entry->warehouse->name,
                ]
                : null,
            'product_id' => $entry->product_id,
            'product_name' => $entry->product_name,
            'unit_id' => $entry->unit_id,
            'unit_name' => $entry->unit_name,
            'opening_date' => $entry->opening_date
                ? \Carbon\Carbon::parse($entry->opening_date)->format('Y-m-d')
                : null,
            'quantity' => $entry->quantity,
            'unit_cost' => $entry->unit_cost,
            'total_cost' => $entry->total_cost,
            'note' => $entry->note,
            'created_by' => $entry->created_by,
            'updated_by' => $entry->updated_by,
            'created_at' => $entry->created_at
                ? \Carbon\Carbon::parse($entry->created_at)->format('d/m/Y H:i')
                : null,
            'updated_at' => $entry->updated_at
                ? \Carbon\Carbon::parse($entry->updated_at)->format('d/m/Y H:i')
                : null,
        ];
    }
}
