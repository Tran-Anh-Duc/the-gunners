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
		echo '<pre>';
		print_r($variable);
		echo '</pre>';
		die;
		return $query->create($data);
	}
}
