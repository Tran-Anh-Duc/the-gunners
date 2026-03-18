<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\BusinessActionRequest;
use App\Http\Requests\BusinessIndexRequest;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Services\SupplierService;
use App\Traits\HasApiPagination;
use Illuminate\Http\JsonResponse;

/**
 * Controller CRUD nhà cung cấp.
 *
 * Nhà cung cấp được dùng cho nhập kho và các phiếu chi,
 * nên việc scope đúng business là rất quan trọng.
 */
class SupplierController extends ApiController
{
    use HasApiPagination;

    public function __construct(private readonly SupplierService $supplierService)
    {
    }

    /**
     * Danh sách nhà cung cấp trong business hiện tại.
     */
    public function index(BusinessIndexRequest $request): JsonResponse
    {
        // Service chịu trách nhiệm lọc theo tenant và điều kiện tìm kiếm; controller chỉ paginate.
        [, $query] = $this->supplierService->paginate(array_merge(
            $request->validated(),
            $request->only($this->supplierService->searchableFilters()),
        ));

        return $this->successResponse(
            'Fetched successfully.',
            'list_success',
            self::HTTP_OK,
            $this->paginate($query, defaultSort: ['column' => 'id', 'order' => 'desc'], defaultPerPage: 10),
        );
    }

    /**
     * Lấy chi tiết một nhà cung cấp.
     */
    public function show(BusinessActionRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            'Fetched successfully.',
            'show_success',
            self::HTTP_OK,
            $this->supplierService->show($id, $request->validated()),
        );
    }

    /**
     * Tạo nhà cung cấp mới.
     */
    public function store(StoreSupplierRequest $request): JsonResponse
    {
        // Controller không tự ghi model; toàn bộ xử lý đều đi qua service.
        return $this->successResponse(
            'Created successfully.',
            'create_success',
            self::HTTP_OK,
            $this->supplierService->create($request->validated()),
        );
    }

    /**
     * Cập nhật nhà cung cấp.
     */
    public function update(UpdateSupplierRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            'Updated successfully.',
            'update_success',
            self::HTTP_OK,
            $this->supplierService->update($id, $request->validated()),
        );
    }

    /**
     * Xóa nhà cung cấp trong phạm vi business hiện tại.
     */
    public function destroy(BusinessActionRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            'Deleted successfully.',
            'delete_success',
            self::HTTP_OK,
            $this->supplierService->delete($id, $request->validated()),
        );
    }
}
