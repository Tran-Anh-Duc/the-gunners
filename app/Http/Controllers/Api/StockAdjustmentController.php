<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\BusinessActionRequest;
use App\Http\Requests\BusinessIndexRequest;
use App\Http\Requests\StoreStockAdjustmentRequest;
use App\Http\Requests\TransitionDocumentStatusRequest;
use App\Http\Requests\UpdateStockAdjustmentRequest;
use App\Services\StockAdjustmentService;
use App\Traits\HasApiPagination;
use Illuminate\Http\JsonResponse;

/**
 * Controller nghiệp vụ kiểm kho/điều chỉnh tồn.
 *
 * Đây là nghiệp vụ giải quyết chênh lệch giữa tồn hệ thống và tồn thực tế,
 * nên logic tính chênh lệch và sync ledger đều nằm ở service.
 */
class StockAdjustmentController extends ApiController
{
    use HasApiPagination;

    public function __construct(private readonly StockAdjustmentService $stockAdjustmentService)
    {
    }

    /**
     * Danh sách chứng từ kiểm kho.
     *
     * Dữ liệu sẽ được lọc theo business và các field tìm kiếm cơ bản trước khi paginate.
     */
    public function index(BusinessIndexRequest $request): JsonResponse
    {
        [, $query] = $this->stockAdjustmentService->paginate(array_merge(
            $request->validated(),
            $request->only($this->stockAdjustmentService->searchableFilters()),
        ));

        return $this->successResponse(
            'Fetched successfully.',
            'list_success',
            self::HTTP_OK,
            $this->paginate($query, defaultSort: ['column' => 'id', 'order' => 'desc'], defaultPerPage: 10),
        );
    }

    /**
     * Chi tiết 1 chứng từ adjustment.
     */
    public function show(BusinessActionRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            'Fetched successfully.',
            'show_success',
            self::HTTP_OK,
            $this->stockAdjustmentService->show($id, $request->validated()),
        );
    }

    /**
     * Tạo chứng từ kiểm kho.
     *
     * Service sẽ tự tính:
     * - expected_qty
     * - difference_qty
     * - line_total
     * rồi mới sync ledger nếu document đã confirm.
     */
    public function store(StoreStockAdjustmentRequest $request): JsonResponse
    {
        return $this->successResponse(
            'Created successfully.',
            'create_success',
            self::HTTP_OK,
            $this->stockAdjustmentService->create($request->validated()),
        );
    }

    /**
     * Cập nhật chứng từ kiểm kho.
     *
     * Nếu danh sách item thay đổi, service sẽ build lại snapshot và đồng bộ ledger từ đầu.
     */
    public function update(UpdateStockAdjustmentRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            'Updated successfully.',
            'update_success',
            self::HTTP_OK,
            $this->stockAdjustmentService->update($id, $request->validated()),
        );
    }

    /**
     * Confirm adjustment để áp chênh lệch vào tồn kho.
     */
    public function confirm(TransitionDocumentStatusRequest $request, int $id): JsonResponse
    {
        // Chỉ service mới nắm được cách adjustment tác động vào ledger và current stock.
        return $this->successResponse(
            'Status updated successfully.',
            'update_success',
            self::HTTP_OK,
            $this->stockAdjustmentService->confirm($id, $request->validated()),
        );
    }

    /**
     * Cancel adjustment và rebuild lại ledger/current stock.
     */
    public function cancel(TransitionDocumentStatusRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            'Status updated successfully.',
            'update_success',
            self::HTTP_OK,
            $this->stockAdjustmentService->cancel($id, $request->validated()),
        );
    }
}
