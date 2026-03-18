<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\BusinessActionRequest;
use App\Http\Requests\BusinessIndexRequest;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\TransitionDocumentStatusRequest;
use App\Http\Requests\UpdatePaymentRequest;
use App\Services\PaymentService;
use App\Traits\HasApiPagination;
use Illuminate\Http\JsonResponse;

/**
 * Controller phiếu thu/chi.
 *
 * Payment liên quan tới tổng đã thu của đơn hàng và các chứng từ mua hàng,
 * vì vậy phần nghiệp vụ được đẩy xuống `PaymentService` để xử lý tập trung.
 */
class PaymentController extends ApiController
{
    use HasApiPagination;
    public function __construct(private readonly PaymentService $paymentService)
    {
    }

    /**
     * Danh sách phiếu thu/chi.
     *
     * Filter text được lấy từ `PaymentService::searchableFilters()`.
     */
    public function index(BusinessIndexRequest $request): JsonResponse
    {
        // Query danh sách đã được service scope theo business trước khi tới controller.
        [, $query] = $this->paymentService->paginate(array_merge(
            $request->validated(),
            $request->only($this->paymentService->searchableFilters()),
        ));

        return $this->successResponse(
            'Fetched successfully.',
            'list_success',
            self::HTTP_OK,
            $this->paginate($query, defaultSort: ['column' => 'id', 'order' => 'desc'], defaultPerPage: 10),
        );
    }

    /**
     * Chi tiết 1 payment trong business hiện tại.
     */
    public function show(BusinessActionRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            'Fetched successfully.',
            'show_success',
            self::HTTP_OK,
            $this->paymentService->show($id, $request->validated()),
        );
    }

    /**
     * Tạo phiếu thu/chi mới.
     *
     * `PaymentService` sẽ xử lý tiếp:
     * - kiểm tra các liên kết `order/stock_in/customer/supplier`;
     * - tạo mã payment nếu cần;
     * - cập nhật payment summary của order nếu có liên kết đơn hàng.
     */
    public function store(StorePaymentRequest $request): JsonResponse
    {
        return $this->successResponse(
            'Created successfully.',
            'create_success',
            self::HTTP_OK,
            $this->paymentService->create($request->validated()),
        );
    }

    /**
     * Cập nhật payment và đồng bộ lại order liên quan nếu cần.
     */
    public function update(UpdatePaymentRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            'Updated successfully.',
            'update_success',
            self::HTTP_OK,
            $this->paymentService->update($id, $request->validated()),
        );
    }

    /**
     * Confirm payment.
     *
     * Trạng thái `paid` có thể làm thay đổi `paid_amount` và `payment_status` của order.
     */
    public function confirm(TransitionDocumentStatusRequest $request, int $id): JsonResponse
    {
        // Toàn bộ side effect của payment được dồn về service để tránh lệch số liệu đơn hàng.
        return $this->successResponse(
            'Status updated successfully.',
            'update_success',
            self::HTTP_OK,
            $this->paymentService->confirm($id, $request->validated()),
        );
    }

    /**
     * Cancel payment.
     *
     * Document được giữ lại để audit, chỉ đổi trạng thái thay vì xóa bản ghi.
     */
    public function cancel(TransitionDocumentStatusRequest $request, int $id): JsonResponse
    {
        // Hủy payment không xóa cứng dữ liệu để vẫn giữ được dấu vết nghiệp vụ.
        return $this->successResponse(
            'Status updated successfully.',
            'update_success',
            self::HTTP_OK,
            $this->paymentService->cancel($id, $request->validated()),
        );
    }
}
