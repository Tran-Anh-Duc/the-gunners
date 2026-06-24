<?php

namespace App\Repositories;

use App\Models\InventoryStockMovement;
use Illuminate\Database\Eloquent\Builder;

class InventoryStockMovementRepository extends BaseRepository
{
	public function __construct(InventoryStockMovement $model)
	{
		$this->model = $model;
	}

	public function getModel(): string
	{
		return InventoryStockMovement::class;
	}

	public function createForBusiness(int $businessId, array $attributes)
	{
		$query = $this->model->newQuery();
		$data = array_merge($attributes, [
			'business_id' => $businessId,
		]);
		return $query->create($data);
	}

	public function countBySource(int $businessId,string $sourceType,int $sourceId): int
	{
		$query = $this->model->newQuery();
		return $query->where([
			'business_id' => $businessId,
			'source_type' => $sourceType,
			'source_id' => $sourceId,
		])->count();
	}

}
