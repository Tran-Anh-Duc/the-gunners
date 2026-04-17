<?php

namespace App\Repositories;

use App\Models\WarehouseDocumentDetail;
use Carbon\Carbon;

class WarehouseDocumentDetailRepository extends BaseRepository
{
    public function getModel()
    {
        return WarehouseDocumentDetail::class;
    }
	
	public function createManyForDocument(int $documentId, int $businessId, array $details): bool
	{
		if (empty($details)) {
			return true;
		}
		
		$now = Carbon::now();
		
		$rows = array_map(function (array $detail) use ($documentId, $businessId, $now) {
			return [
				'warehouse_document_id' => $documentId,
				'product_id' => $detail['product_id'] ?? null,
				'product_name' => $detail['product_name'] ?? null,
				'unit_id' => $detail['unit_id'] ?? null,
				'unit_name' => $detail['unit_name'] ?? null,
				'quantity' => $detail['quantity'] ?? 0,
				'unit_price' => $detail['unit_price'] ?? 0,
				'subtotal' => $detail['subtotal'] ?? 0,
				'tax_rate' => $detail['tax_rate'] ?? 0,
				'tax_price' => $detail['tax_price'] ?? 0,
				'total_price' => $detail['total_price'] ?? 0,
				'note' => $detail['note'] ?? null,
				'created_at' => $now,
				'updated_at' => $now,
			];
		}, $details);
		
		return $this->model->newQuery()->insert($rows);
	}
}
