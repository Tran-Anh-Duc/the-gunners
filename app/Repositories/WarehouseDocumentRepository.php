<?php

namespace App\Repositories;

use App\Models\WarehouseDocument;
use Illuminate\Database\Eloquent\Builder;
use App\Models\BusinessUser;
use Illuminate\Database\Eloquent\Model;

class WarehouseDocumentRepository extends BaseRepository
{
	public function __construct(WarehouseDocument $warehouseDocument)
	{
		$this->model = $warehouseDocument;
	}
	
	public function getModel(): string
	{
		return WarehouseDocument::class;
	}
	
	public function queryForBusiness(int $businessId, array $filters = []): Builder
	{
		$query = $this->model->newQuery()->with([
			'business',
			'warehouse',
			'creator',
			'updater',
			'approver',
		]);
		
		$query->where('business_id', $businessId);
		$query->select('warehouse_documents.*');
		if (!empty($filters['document_code'])) {
			$query->where('document_code', 'like', '%' . $filters['document_code'] . '%');
		}
		
		if (!empty($filters['document_type'])) {
			$query->where('document_type', $filters['document_type']);
		}
		
		if (!empty($filters['warehouse_id'])) {
			$query->where('warehouse_id', $filters['warehouse_id']);
		}
		if (!empty($filters['status'])) {
			$query->where('status', $filters['status']);
		}
		if (!empty($filters['document_date_from'])) {
			$query->whereDate('document_date', '>=', $filters['document_date_from']);
		}
		
		if (!empty($filters['document_date_to'])) {
			$query->whereDate('document_date', '<=', $filters['document_date_to']);
		}
		return $query->orderByDesc('warehouse_documents.id');
	}
	
	public function findForBusinessOrFail(int $id, int $businessId)
	{
		$query = $this->model->newQuery()->with([
			'business',
			'warehouse',
			'creator',
			'updater',
			'approver',
			'details.product',
			'details.unit',
		]);
		$query->where('business_id', $businessId);
		$query->select('warehouse_documents.*');
		return $query->findOrFail($id);
	}
	
	public function createForBusiness(int $businessId, array $attributes)
	{
		$query = $this->model->newQuery();
		
		$data = array_merge($attributes, [
			'business_id' => $businessId,
		]);
		
		return $query->create($data);
	}
	
	public function updateForBusiness(int $businessId, array $attributes,int $id)
	{
		$document = $this->model->newQuery()
			->where('business_id', $businessId)
			->findOrFail($id);
		
		unset($attributes['business_id'], $attributes['document_code'], $attributes['created_by']);
		$attributes['updated_by'] = auth()->id();
		
		$document->update($attributes);
		
		return $document->refresh();
	}
	
	public function existsConfirmedDocumentForProductInWarehouse(int $businessId,int $warehouseId,int $productId): bool
	{
		$query = $this->model->newQuery();
		$query->where('business_id', $businessId)
			->where('warehouse_id', $warehouseId)
			->where('status', WarehouseDocument::STATUS_CONFIRMED)
			->wherehas('details', function (Builder $query) use ($productId) {
				$query->where('product_id', $productId);
			});
		return $query->exists();
	}
	
	
	
	

	
}
