<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BusinessActionRequest;
use App\Http\Requests\StoreWarehouseDocumentRequest;
use App\Http\Requests\UpdateWarehouseDocumentRequest;
use App\Http\Requests\WarehouseDocumentListRequest;
use App\Services\WarehouseDocumentService;
use App\Traits\ApiResponse;
use App\Traits\HasApiPagination;
use App\Transformers\WarehouseDocumentDetailTransform;
use App\Transformers\WarehouseDocumentTransform;
use Illuminate\Http\JsonResponse;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class WarehouseDocumentController extends ApiController
{
    use ApiResponse;
    use HasApiPagination;
	
	public function __construct(
		protected WarehouseDocumentService $warehouseDocumentService,
		protected WarehouseDocumentTransform $warehouseDocumentTransform,
		protected WarehouseDocumentDetailTransform $warehouseDocumentDetailTransform
	)
	{
	}
	
	/**
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function index(WarehouseDocumentListRequest $request): JsonResponse
    {
		$pagination = $this->paginate(
			query: $this->warehouseDocumentService->listQuery($request->validated()),
			transformer: $this->warehouseDocumentTransform,
			defaultPerPage: 10,
		);

        return $this->successResponse(
            message: __('messages.user.user_list_success'),
            code: 'user_list_success',
            httpStatus: Controller::HTTP_OK,
            data: $pagination,
        );
    }
	
	public function show(BusinessActionRequest $request, int $id): JsonResponse
	{
		$warehouseDocument = $this->warehouseDocumentService->show($id, $request->validated());
		
		return $this->successResponse(
			message: 'Fetched successfully.',
			code: 'show_success',
			httpStatus: self::HTTP_OK,
			data: $this->transformData($warehouseDocument, $this->warehouseDocumentDetailTransform)['data'] ?? [],
		);
	}

    public function store(StoreWarehouseDocumentRequest $request): JsonResponse
    {
        return $this->successResponse(
            'Created successfully.',
            'create_success',
            self::HTTP_OK,
            $this->warehouseDocumentService->create($request->validated()),
        );
    }

    public function update(UpdateWarehouseDocumentRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            'Updated successfully.',
            'update_success',
            self::HTTP_OK,
            $this->warehouseDocumentService->update($id, $request->validated()),
        );
    }

    public function destroy(BusinessActionRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            'Deleted successfully.',
            'delete_success',
            self::HTTP_OK,
            $this->warehouseDocumentService->delete($id, $request->validated()),
        );
    }
}
