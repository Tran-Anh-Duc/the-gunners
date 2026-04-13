<?php
	
	namespace App\Transformers;
	
	use App\Models\WarehouseDocument;
	use League\Fractal\TransformerAbstract;
	
	class WarehouseDocumentTransform extends TransformerAbstract
	{
		protected array $defaultIncludes = [];
		
		protected array $availableIncludes = [];
		
		public function __construct(
			protected ?WarehouseDocumentDetailTransform $warehouseDocumentDetailTransform = null,
		)
		{
			$this->warehouseDocumentDetailTransform ??= new WarehouseDocumentDetailTransform();
		}
		
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
				'approved_at' => $entry->approved_at,
				'created_at' => $entry->created_at
					? \Carbon\Carbon::parse($entry->created_at)->format('d/m/Y H:i')
					: null,
				'updated_at' => $entry->updated_at,
				'deleted_at' => $entry->deleted_at,
			];
		}
	}
