<?php

namespace App\Services;

use App\Models\InventoryOpening;
use App\Repositories\InventoryOpeningRepository;
use App\Repositories\WarehouseDocumentRepository;
use App\Support\BusinessContext;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryOpeningService extends BaseBusinessCrudService
{
	use ApiResponse;
	
	protected array $with = [
		'business',
		'warehouse',
		'creator',
		'updater',
		'product',
		'unit'
	];
	
	protected array $searchable = [
		'keyword',
		'warehouse_id',
		'product_id',
		'created_by',
		'inventory_date_from',
		'inventory_date_to',
	];
	
	public function __construct(
		BusinessContext                              $businessContext,
		private readonly InventoryOpeningRepository  $inventoryOpeningRepository,
		private readonly WarehouseDocumentRepository $warehouseDocumentRepository
	)
	{
		parent::__construct($businessContext);
	}
	
	public function listQuery(array $filters): Builder
	{
		$businessId = $this->businessContext->resolveBusinessId($filters['business_id'] ?? null);
		return $this->inventoryOpeningRepository->queryForBusiness($businessId, $filters);
	}
	
	public function create(array $data): InventoryOpening
	{
		return DB::transaction(function () use ($data) {
			$businessId = $this->resolveBusinessId($data);
			$productId = $data['product_id'] ?? null;
			$warehouseId = $data['warehouse_id'] ?? null;
			$resultCheckExitWareHouseDocument = $this->existsConfirmedDocumentForProductInWarehouse($businessId, $warehouseId, $productId);
			
			if ($resultCheckExitWareHouseDocument) {
				throw ValidationException::withMessages([
					'product_id' => __('messages.inventory.opening_exists_movement'),
				]);
			}
			$payload = $this->payloadForSave($data, $businessId, false);
			
			$document = $this->inventoryOpeningRepository->createForBusiness($businessId, $payload);
			
			return $document->load($this->with);
			
		});
		
	}
	
	public function update(int $id, array $data): InventoryOpening
	{
		return DB::transaction(function () use ($id, $data) {
			$businessId = $this->resolveBusinessId($data);
			$productId = $data['product_id'] ?? null;
			$warehouseId = $data['warehouse_id'] ?? null;
			$resultCheckExitWareHouseDocument = $this->existsConfirmedDocumentForProductInWarehouse($businessId, $warehouseId, $productId);
			
			if ($resultCheckExitWareHouseDocument) {
				throw ValidationException::withMessages([
					'product_id' => __('messages.inventory.opening_exists_movement'),
				]);
			}
			$model = $this->inventoryOpeningRepository->findForBusinessOrFail($businessId, $id);
			
			$payload = $this->payloadForSave($data, $businessId, true, $model);
			
			$document = $this->inventoryOpeningRepository->updateForBusiness($businessId, $payload, $id);
			
			return $document->load($this->with);
		});
	}
	
	public function show(int $id, array $data): Model
	{
		$businessId = $this->businessContext->resolveBusinessId();
		return $this->inventoryOpeningRepository->findForBusinessOrFail($businessId,$id);
	}
	
	protected function payloadForSave(array $data, int $businessId, bool $isUpdate = false, ?InventoryOpening $model = null): array
	{
		$quantity = (float)($data['quantity'] ?? $model?->quantity ?? 0);
		$unitCost = (float)($data['unit_cost'] ?? $model?->unit_cost ?? 0);
		
		$payload = [
			'business_id' => $businessId,
			'warehouse_id' => $data['warehouse_id'] ?? $model->warehouse_id,
			'product_id' => $data['product_id'] ?? $model->product_id,
			'product_name' => $data['product_name'] ?? $model->product_name,
			'unit_id' => $data['unit_id'] ?? $model->unit_id,
			'unit_name' => $data['unit_name'] ?? $model->unit_name,
			'opening_date' => $data['opening_date'] ?? $model->opening_date,
			'quantity' => $quantity,
			'unit_cost' => $unitCost,
			'total_cost' => round($quantity * $unitCost, 2),
			'note' => $data['note'] ?? $model->note,
		];
		
		if ($isUpdate) {
			$payload['updated_by'] = auth()->id();
		} else {
			$payload['created_by'] = auth()->id();
		}
		
		return $payload;
	}
	
	protected function existsConfirmedDocumentForProductInWarehouse(int $businessId, int $warehouseId, int $productId): bool
	{
		return $this->warehouseDocumentRepository->existsConfirmedDocumentForProductInWarehouse(
			$businessId, $warehouseId, $productId
		);
	}
	
	
	
	
	
}