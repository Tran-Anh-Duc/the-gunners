<?php

namespace App\Repositories;

use App\Models\WarehouseDocument;
use Illuminate\Database\Eloquent\Builder;
use App\Models\BusinessUser;

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
	
}
