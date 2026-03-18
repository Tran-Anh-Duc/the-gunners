<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\BusinessActionRequest;
use App\Http\Requests\BusinessIndexRequest;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Services\CustomerService;
use App\Traits\HasApiPagination;
use Illuminate\Http\JsonResponse;

/**
 * Controller CRUD khách hàng.
 *
 * Controller này không tự query model hay xử lý nghiệp vụ:
 * - request chịu trách nhiệm validate đầu vào;
 * - service xử lý business scope và thao tác dữ liệu;
 * - controller chỉ điều phối paginate và trả JSON response.
 */
class CustomerController extends ApiController
{
    use HasApiPagination;

    public function __construct(private readonly CustomerService $customerService)
    {
    }

    /**
     * Danh sách khách hàng trong business hiện tại.
     *
     * Cách xử lý:
     * - lấy filter đã validate từ request;
     * - chuyển phần tìm kiếm theo tên, điện thoại, email cho service;
     * - paginate kết quả và trả về response chuẩn.
     */
    public function index(BusinessIndexRequest $request): JsonResponse
    {
        // Controller chỉ dựng response; query đã được service khóa theo đúng business.
        [, $query] = $this->customerService->paginate(array_merge(
            $request->validated(),
            $request->only($this->customerService->searchableFilters()),
        ));

        return $this->successResponse(
            'Fetched successfully.',
            'list_success',
            self::HTTP_OK,
            $this->paginate($query, defaultSort: ['column' => 'id', 'order' => 'desc'], defaultPerPage: 10),
        );
    }

    /**
     * Lấy chi tiết một khách hàng.
     *
     * Bản ghi chỉ được đọc nếu thuộc đúng business hiện tại.
     */
    public function show(BusinessActionRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            'Fetched successfully.',
            'show_success',
            self::HTTP_OK,
            $this->customerService->show($id, $request->validated()),
        );
    }

    /**
     * Tạo khách hàng mới trong business hiện tại.
     *
     * `business_id` thực tế sẽ do service và repository suy ra hoặc gắn vào,
     * không để frontend tự quyết định bản ghi thuộc tenant nào.
     */
    public function store(StoreCustomerRequest $request): JsonResponse
    {
        // Luồng tạo mới giữ mỏng ở controller để toàn bộ nghiệp vụ tập trung trong service.
        return $this->successResponse(
            'Created successfully.',
            'create_success',
            self::HTTP_OK,
            $this->customerService->create($request->validated()),
        );
    }

    /**
     * Cập nhật khách hàng hiện có.
     */
    public function update(UpdateCustomerRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            'Updated successfully.',
            'update_success',
            self::HTTP_OK,
            $this->customerService->update($id, $request->validated()),
        );
    }

    /**
     * Xóa khách hàng trong business hiện tại.
     *
     * Điều này giúp tránh thao tác nhầm sang dữ liệu của tenant khác.
     */
    public function destroy(BusinessActionRequest $request, int $id): JsonResponse
    {
        // Service sẽ đảm bảo thao tác xóa chỉ diễn ra trên dữ liệu cùng business.
        return $this->successResponse(
            'Deleted successfully.',
            'delete_success',
            self::HTTP_OK,
            $this->customerService->delete($id, $request->validated()),
        );
    }
}
