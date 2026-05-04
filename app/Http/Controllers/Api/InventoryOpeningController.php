<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BusinessActionRequest;
use App\Http\Requests\InventoryOpeningIndexRequest;
use App\Http\Requests\StoreInventoryOpeningRequest;
use App\Http\Requests\UpdateInventoryOpeningRequest;
use App\Services\InventoryOpeningService;
use App\Traits\ApiResponse;
use App\Traits\HasApiPagination;
use App\Transformers\InventoryOpeningTransformer;
use Illuminate\Http\JsonResponse;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class InventoryOpeningController extends ApiController
{
	use ApiResponse;
    use HasApiPagination;
	
	public function __construct(
		protected InventoryOpeningService $inventoryOpeningService,
		protected InventoryOpeningTransformer $inventoryOpeningTransformer,
	)
	{
	
	}
	
	/**
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function index(InventoryOpeningIndexRequest $request): JsonResponse
    {
	    $pagination = $this->inventoryOpeningService->groupedByWarehouse(
		    $request->validated()
	    );
		
       return $this->successResponse(
            message: __('messages.inventory_opening.list_success'),
            code: 'user_list_success',
            httpStatus: Controller::HTTP_OK,
            data: $pagination,
        );
    }

    public function store(StoreInventoryOpeningRequest $request): JsonResponse
    {
        return $this->successResponse(
            'Created successfully.',
            'create_success',
            self::HTTP_OK,
            $this->inventoryOpeningService->create($request->validated()),
        );
    }

    public function show(BusinessActionRequest $request, int $id): JsonResponse
    {
		$inventoryOpening = $this->inventoryOpeningService->show($id, $request->validated());
	    
	    return $this->successResponse(
		    message: 'Fetched successfully.',
		    code: 'show_success',
		    httpStatus: self::HTTP_OK,
		    data: $this->transformData($inventoryOpening, $this->inventoryOpeningTransformer)['data'] ?? [],
	    );
    }

    public function update(UpdateInventoryOpeningRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            'Updated successfully.',
            'update_success',
            self::HTTP_OK,
            $this->inventoryOpeningService->update($id, $request->validated())
        );
    }

    public function destroy(BusinessActionRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            'Deleted successfully.',
            'delete_success',
            self::HTTP_OK,
            []
        );
    }
}
