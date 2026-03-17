<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BusinessActionRequest;
use App\Http\Requests\BusinessIndexRequest;
use App\Http\Requests\StoreWarehouseRequest;
use App\Http\Requests\UpdateWarehouseRequest;
use App\Services\WarehouseService;
use App\Traits\HasApiPagination;
use Illuminate\Http\JsonResponse;

class WarehouseController extends Controller
{
    use HasApiPagination;

    public function __construct(private readonly WarehouseService $warehouseService)
    {
    }

    public function index(BusinessIndexRequest $request): JsonResponse
    {
        [, $query] = $this->warehouseService->paginate(array_merge(
            $request->validated(),
            $request->only($this->warehouseService->searchableFilters()),
        ));

        return $this->successResponse(
            'Fetched successfully.',
            'list_success',
            self::HTTP_OK,
            $this->paginate($query, defaultSort: ['column' => 'id', 'order' => 'desc'], defaultPerPage: 10),
        );
    }

    public function show(BusinessActionRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            'Fetched successfully.',
            'show_success',
            self::HTTP_OK,
            $this->warehouseService->show($id, $request->validated()),
        );
    }

    public function store(StoreWarehouseRequest $request): JsonResponse
    {
        return $this->successResponse(
            'Created successfully.',
            'create_success',
            self::HTTP_OK,
            $this->warehouseService->create($request->validated()),
        );
    }

    public function update(UpdateWarehouseRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            'Updated successfully.',
            'update_success',
            self::HTTP_OK,
            $this->warehouseService->update($id, $request->validated()),
        );
    }

    public function destroy(BusinessActionRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            'Deleted successfully.',
            'delete_success',
            self::HTTP_OK,
            $this->warehouseService->delete($id, $request->validated()),
        );
    }
}
