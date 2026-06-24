<?php

namespace App\Repositories;

use App\Models\InventoryStock;

class InventoryStockRepository extends BaseRepository
{
	public function __construct(InventoryStock $inventoryStock)
	{
		$this->model = $inventoryStock;
	}

	public function getModel():string
	{
		return InventoryStock::class;
	}

	public function createForBusiness(int $businessId, array $attributes)
	{
		$query = $this->model->newQuery();
		$data = array_merge($attributes, [
			'business_id' => $businessId,
		]);
		return $query->create($data);
	}

	public function findByWarehouseAndProduct(int $businessId, int $productId, int $warehouseId)
	{
		$query = $this->model->newQuery();
		$data = $query->where([
			'warehouse_id' => $warehouseId,
			'product_id' => $productId,
			'business_id' => $businessId,
		]);
		return $data->first();
	}

	public function updateForBusiness(int $businessId, array $attributes, int $stockId) : InventoryStock
	{
		$query = $this->model->newQuery();
		$data = $query->where([
			'business_id' => $businessId,
			'id' => $stockId,
		])->firstOrFail();
		$data->update($attributes);
		return $data->fresh();
	}
}
