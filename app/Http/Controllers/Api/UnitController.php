<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\BusinessActionRequest;
use App\Http\Requests\BusinessIndexRequest;
use App\Http\Requests\StoreUnitRequest;
use App\Http\Requests\UpdateUnitRequest;
use App\Services\UnitService;
use App\Traits\HasApiPagination;
use Illuminate\Http\JsonResponse;

/**
 * Controller CRUD đơn vị tính.
 *
 * Unit là master data nền của sản phẩm.
 * Controller này giữ luồng `request -> service -> response` thật gọn
 * để business rule luôn nằm ở tầng service.
 */
class UnitController extends ApiController
{
    use HasApiPagination;

    public function __construct(private readonly UnitService $unitService)
    {
    }

    /**
     * Danh sách đơn vị tính trong business hiện tại.
     */
    public function index(BusinessIndexRequest $request): JsonResponse
    {
        // Việc lọc theo `code` hoặc `name` được giao cho service để tái sử dụng thống nhất.
        [, $query] = $this->unitService->paginate(array_merge(
            $request->validated(),
            $request->only($this->unitService->searchableFilters()),
        ));

        return $this->successResponse(
            'Fetched successfully.',
            'list_success',
            self::HTTP_OK,
            $this->paginate($query, defaultSort: ['column' => 'id', 'order' => 'desc'], defaultPerPage: 10),
        );
    }

    /**
     * Lấy chi tiết một đơn vị tính.
     */
    public function show(BusinessActionRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            'Fetched successfully.',
            'show_success',
            self::HTTP_OK,
            $this->unitService->show($id, $request->validated()),
        );
    }

    /**
     * Tạo đơn vị tính mới.
     */
    public function store(StoreUnitRequest $request): JsonResponse
    {
        return $this->successResponse(
            'Created successfully.',
            'create_success',
            self::HTTP_OK,
            $this->unitService->create($request->validated()),
        );
    }

    /**
     * Cập nhật đơn vị tính.
     */
    public function update(UpdateUnitRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            'Updated successfully.',
            'update_success',
            self::HTTP_OK,
            $this->unitService->update($id, $request->validated()),
        );
    }

    /**
     * Xóa đơn vị tính trong phạm vi business hiện tại.
     */
    public function destroy(BusinessActionRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            'Deleted successfully.',
            'delete_success',
            self::HTTP_OK,
            $this->unitService->delete($id, $request->validated()),
        );
    }
}
