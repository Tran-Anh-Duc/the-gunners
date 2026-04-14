<?php
	
	namespace App\Transformers;
	
	use App\Models\WarehouseDocument;
	use League\Fractal\TransformerAbstract;
	
	class WarehouseDocumentDetailTransform extends TransformerAbstract
	{
		protected array $defaultIncludes = [];
		
		protected array $availableIncludes = [];
		
		public function transform(WarehouseDocument $entry): array
		{
			return [
				'id' => $entry->id,
				'business_id' => $entry->business_id,
				'document_code' => $entry->document_code,
				'document_type' => $entry->document_type,
				'document_date' => $entry->document_date
					? \Carbon\Carbon::parse($entry->document_date)->format('d/m/Y H:i')
					: null,
				'status' => $entry->status,
				'reference_code' => $entry->reference_code,
				'subtotal_amount' => $entry->subtotal_amount,
				'tax_amount' => $entry->tax_amount,
				'total_amount' => $entry->total_amount,
				'note' => $entry->note,
				'warehouse' => $entry->relationLoaded('warehouse') && $entry->warehouse
					? [
						'id' => $entry->warehouse->id,
						'code' => $entry->warehouse->code,
						'name' => $entry->warehouse->name,
					]
					: null,
				'creator' => $entry->relationLoaded('creator') && $entry->creator
					? [
						'id' => $entry->creator->id,
						'name' => $entry->creator->name,
						'email' => $entry->creator->email,
					]
					: null,
				'updater' => $entry->relationLoaded('updater') && $entry->updater
					? [
						'id' => $entry->updater->id,
						'name' => $entry->updater->name,
						'email' => $entry->updater->email,
					]
					: null,
				'approver' => $entry->relationLoaded('approver') && $entry->approver
					? [
						'id' => $entry->approver->id,
						'name' => $entry->approver->name,
						'email' => $entry->approver->email,
					]
					: null,
				'approved_at' => $entry->approved_at
					? \Carbon\Carbon::parse($entry->approved_at)->format('d/m/Y H:i')
					: null,
				'details' => $entry->relationLoaded('details')
					? $entry->details->map(function ($detail): array {
						return [
							'id' => $detail->id,
							'warehouse_document_id' => $detail->warehouse_document_id,
							'product_id' => $detail->product_id,
							'product_name' => $detail->product_name,
							'unit_id' => $detail->unit_id,
							'unit_name' => $detail->unit_name,
							'quantity' => $detail->quantity,
							'unit_price' => $detail->unit_price,
							'subtotal' => $detail->subtotal,
							'tax_rate' => $detail->tax_rate,
							'tax_price' => $detail->tax_price,
							'total_price' => $detail->total_price,
							'note' => $detail->note,
							'product' => $detail->relationLoaded('product') && $detail->product
								? [
									'id' => $detail->product->id,
									'sku' => $detail->product->sku,
									'name' => $detail->product->name,
								]
								: null,
							'unit' => $detail->relationLoaded('unit') && $detail->unit
								? [
									'id' => $detail->unit->id,
									'code' => $detail->unit->code,
									'name' => $detail->unit->name,
								]
								: null,
							'created_at' => $detail->created_at,
							'updated_at' => $detail->updated_at,
						];
					})->values()->all()
					: [],
				'created_at' => $entry->created_at
					? \Carbon\Carbon::parse($entry->created_at)->format('d/m/Y H:i')
					: null,
				'updated_at' => $entry->updated_at
					? \Carbon\Carbon::parse($entry->updated_at)->format('d/m/Y H:i')
					: null,
				'deleted_at' => $entry->deleted_at,
			];
		}
	}
