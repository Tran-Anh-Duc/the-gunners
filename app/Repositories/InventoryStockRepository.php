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
}
