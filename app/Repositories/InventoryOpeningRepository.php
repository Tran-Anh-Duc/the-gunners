<?php

namespace App\Repositories;

use App\Models\InventoryOpening;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class InventoryOpeningRepository extends BaseRepository
{
	public function __construct(InventoryOpening $model)
	{
		$this->model = $model;
	}
	
	public function getModel():string
	{
		return InventoryOpening::class;
	}
	
	public function paginateWarehouseIdsForOpening(
		int   $businessId,
		array $filters,
		int   $perPage,
		int   $page
	): LengthAwarePaginator
	{
		return $this->model->newQuery()
			->select('warehouse_id')
			->where('business_id', $businessId)
			->when(!empty($filters['warehouse_id']), function ($query) use ($filters) {
				$query->where('warehouse_id', $filters['warehouse_id']);
			})
			->groupBy('warehouse_id')
			->orderBy('warehouse_id')
			->paginate($perPage, ['*'], 'page', $page);
	}
	
	private function applyFilters(Builder $query, array $filters): Builder
	{
		if (!empty($filters['product_id'])) {
			$query->where('product_id', $filters['product_id']);
		}
		
		if (!empty($filters['warehouse_id'])) {
			$query->where('warehouse_id', $filters['warehouse_id']);
		}
		
		if (!empty($filters['opening_date_from'])) {
			$query->whereDate('opening_date', '>=', $filters['opening_date_from']);
		}
		
		if (!empty($filters['opening_date_to'])) {
			$query->whereDate('opening_date', '<=', $filters['opening_date_to']);
		}
		
		return $query;
	}
	
	public function queryForBusiness(int $businessId, array $filters = []): Builder
	{
		$query = $this->model->newQuery()
			->with(['business', 'warehouse', 'creator', 'updater'])
			->where('business_id', $businessId)
			->select('inventory_openings.*');
		
		return $this->applyFilters($query, $filters)
			->orderByDesc('inventory_openings.id');
	}
	
	public function getGroupedByWarehouse(
		int   $businessId,
		array $filters = [],
		array $warehouseIds = []
	): Collection
	{
		$query = $this->model->newQuery()
			->with(['business', 'warehouse', 'creator', 'updater'])
			->where('business_id', $businessId)
			->select('inventory_openings.*');
		
		if (!empty($warehouseIds)) {
			$query->whereIn('warehouse_id', $warehouseIds);
		}
		
		return $this->applyFilters($query, $filters)
			->orderBy('warehouse_id')
			->orderBy('inventory_openings.id')
			->get()
			->groupBy('warehouse_id');
	}
	
	public function createForBusiness(int $businessId, array $attributes)
	{
		$query = $this->model->newQuery();
		
		$data = array_merge($attributes, [
			'business_id' => $businessId,
		]);
		
		return $query->create($data);
	}
	
	public function updateForBusiness(int $businessId, array $attributes, int $id)
	{
		$document = $this->model->newQuery()
			->where('business_id', $businessId)
			->findOrFail($id);
		unset($attributes['business_id']);
		$attributes['updated_by'] = auth()->id();
		$document->update($attributes);
		
		return $document->refresh();
	}
	
	public function findForBusinessOrFail(int $businessId, int $id)
	{
		return $this->model->newQuery()
			->where('business_id', $businessId)
			->where('id', $id)
			->firstOrFail();
	}
	
}
