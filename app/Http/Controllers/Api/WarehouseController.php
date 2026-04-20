<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\BusinessActionRequest;
use App\Http\Requests\StoreWarehouseRequest;
use App\Http\Requests\UpdateWarehouseRequest;
use App\Http\Requests\WarehouseIndexRequest;
use App\Services\WarehouseService;
use App\Traits\HasApiPagination;
use Illuminate\Http\JsonResponse;

/**
 * Controller CRUD kho.
 *
 * Kho là một chiều dữ liệu bắt buộc của bài toán tồn kho.
 * Vì vậy mọi thao tác đọc ghi đều phải đi đúng business scope.
 */
class WarehouseController extends ApiController
{
    use HasApiPagination;

    public function __construct(private readonly WarehouseService $warehouseService)
    {
    }

    /**
     * Danh sách kho trong business hiện tại.
     */
    public function index(WarehouseIndexRequest $request): JsonResponse
    {
        [, $query] = $this->warehouseService->paginate(
	        array_merge($request->validated(),$request->only($this->warehouseService->searchableFilters()))
        );
		
		if ($request->boolean('is_option')) {
            return $this->successResponse(
                'Fetched successfully.',
                'list_success',
                self::HTTP_OK,
                $query->select(['id', 'name','code'])->get()
            );
        }
		
        return $this->successResponse(
            'Fetched successfully.',
            'list_success',
            self::HTTP_OK,
            $this->paginate($query, defaultSort: ['column' => 'id', 'order' => 'desc'], defaultPerPage: 10),
        );
    }

    /**
     * Lấy chi tiết một kho.
     */
    public function show(BusinessActionRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            'Fetched successfully.',
            'show_success',
            self::HTTP_OK,
            $this->warehouseService->show($id, $request->validated()),
        );
    }

    /**
     * Tạo kho mới.
     */
    public function store(StoreWarehouseRequest $request): JsonResponse
    {
        return $this->successResponse(
            'Created successfully.',
            'create_success',
            self::HTTP_OK,
            $this->warehouseService->create($request->validated()),
        );
    }

    /**
     * Cập nhật thông tin kho.
     */
    public function update(UpdateWarehouseRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            'Updated successfully.',
            'update_success',
            self::HTTP_OK,
            $this->warehouseService->update($id, $request->validated()),
        );
    }

    /**
     * Xóa kho trong business hiện tại.
     */
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
