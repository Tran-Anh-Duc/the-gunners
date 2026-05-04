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
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Ramsey\Collection\Collection;
use Carbon\Carbon;

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
	
	/**
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function groupedByWarehouse(array $filters): array
	{
		$businessId = $this->businessContext->resolveBusinessId($filters['business_id'] ?? null);
		
		$perPage = (int)request()->get('per_page', 10);
		$page = (int)request()->get('page', 1);
		
		$paginator = $this->inventoryOpeningRepository
			->paginateWarehouseIdsForOpening($businessId, $filters, $perPage, $page);
		
		$warehouseIds = $paginator->getCollection()
			->pluck('warehouse_id')
			->values();
		$openings = $this->inventoryOpeningRepository->getGroupedByWarehouse($businessId, $filters, $warehouseIds->all());
		
		$items = collect($openings)->map(function ($rows, $warehouseId) {
			$first = collect($rows)->first();
			return [
				'warehouse_id' => (int)$warehouseId,
				'warehouse' => ($first['warehouse'])->toArray() ?? null,
				'opening_date' => !empty($first['opening_date'])
					? Carbon::parse($first['opening_date'])->format('Y-m-d')
					: null,
				'total_quantity' => collect($rows)->sum(fn($row) => (float)$row['quantity']),
				
				'total_cost' => collect($rows)->sum(fn($row) => (float)$row['total_cost']),
				'details' => collect($rows)->map(function ($row) {
					return [
						'id' => $row['id'],
						'product_id' => $row['product_id'],
						'product_name' => $row['product_name'],
						'unit_id' => $row['unit_id'],
						'unit_name' => $row['unit_name'],
						'quantity' => $row['quantity'],
						'unit_cost' => $row['unit_cost'],
						'total_cost' => $row['total_cost'],
						'note' => $row['note'],
					];
				})->values(),
			];
		})->values();
		
		return [
			'items' => $items,
			'current_page' => $paginator->currentPage(),
			'last_page' => $paginator->lastPage(),
			'per_page' => $paginator->perPage(),
			'total' => $paginator->total(),
		];
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