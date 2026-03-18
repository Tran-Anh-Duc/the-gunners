<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\BusinessActionRequest;
use App\Http\Requests\BusinessIndexRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\TransitionDocumentStatusRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Services\OrderService;
use App\Traits\HasApiPagination;
use Illuminate\Http\JsonResponse;

/**
 * Controller đơn hàng.
 *
 * Luồng chung:
 * - request validate payload;
 * - `OrderService` xử lý business scope và nghiệp vụ;
 * - controller trả JSON response nhất quán cho frontend.
 */
class OrderController extends ApiController
{
    use HasApiPagination;
    public function __construct(private readonly OrderService $orderService)
    {
    }

    /**
     * Danh sách đơn hàng.
     *
     * Thành phần đầu vào:
     * - `BusinessIndexRequest`;
     * - các filter text lấy từ `searchableFilters()`.
     *
     * Kết quả:
     * - query đã được paginate;
     * - dữ liệu vẫn nằm trong business hiện tại.
     */
    public function index(BusinessIndexRequest $request): JsonResponse
    {
        // Controller mỏng: validate ở request, business scope và nghiệp vụ ở service.
        [, $query] = $this->orderService->paginate(array_merge(
            $request->validated(),
            $request->only($this->orderService->searchableFilters()),
        ));

        return $this->successResponse(
            'Fetched successfully.',
            'list_success',
            self::HTTP_OK,
            $this->paginate($query, defaultSort: ['column' => 'id', 'order' => 'desc'], defaultPerPage: 10),
        );
    }

    /**
     * Xem chi tiết một đơn hàng trong business hiện tại.
     */
    public function show(BusinessActionRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            'Fetched successfully.',
            'show_success',
            self::HTTP_OK,
            $this->orderService->show($id, $request->validated()),
        );
    }

    /**
     * Tạo đơn hàng mới.
     *
     * Service sẽ:
     * - kiểm tra warehouse và customer cùng business;
     * - dựng snapshot item;
     * - tính lại toàn bộ tổng tiền ở backend.
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        return $this->successResponse(
            'Created successfully.',
            'create_success',
            self::HTTP_OK,
            $this->orderService->create($request->validated()),
        );
    }

    /**
     * Cập nhật đơn hàng.
     *
     * Nếu request gửi lại `items`, service sẽ rebuild item và tính lại tổng tiền
     * để tránh lệch dữ liệu giữa header và chi tiết.
     */
    public function update(UpdateOrderRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            'Updated successfully.',
            'update_success',
            self::HTTP_OK,
            $this->orderService->update($id, $request->validated()),
        );
    }

    /**
     * Confirm đơn hàng.
     *
     * Hiện tại thao tác này chỉ đổi status qua service,
     * nhưng đây là điểm mở rộng cho workflow chặt hơn về sau.
     */
    public function confirm(TransitionDocumentStatusRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            'Status updated successfully.',
            'update_success',
            self::HTTP_OK,
            $this->orderService->confirm($id, $request->validated()),
        );
    }

    /**
     * Cancel đơn hàng và giữ lại document để audit.
     */
    public function cancel(TransitionDocumentStatusRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            'Status updated successfully.',
            'update_success',
            self::HTTP_OK,
            $this->orderService->cancel($id, $request->validated()),
        );
    }
}
