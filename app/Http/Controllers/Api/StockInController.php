<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\BusinessActionRequest;
use App\Http\Requests\BusinessIndexRequest;
use App\Http\Requests\StoreStockInRequest;
use App\Http\Requests\TransitionDocumentStatusRequest;
use App\Http\Requests\UpdateStockInRequest;
use App\Services\StockInService;
use App\Traits\HasApiPagination;
use Illuminate\Http\JsonResponse;

/**
 * Controller phiếu nhập kho.
 *
 * Controller chỉ giữ vai trò giao tiếp HTTP;
 * mọi tính toán tổng tiền, sync ledger và cập nhật current stock đều nằm ở service.
 */
class StockInController extends ApiController
{
    use HasApiPagination;
    public function __construct(private readonly StockInService $stockInService)
    {
    }

    /**
     * Danh sách phiếu nhập kho.
     *
     * Filter chính:
     * - stock_in_no
     * - status
     * - stock_in_type
     * - reference_no
     */
    public function index(BusinessIndexRequest $request): JsonResponse
    {
        // Tìm kiếm theo số phiếu, trạng thái, loại chứng từ... được xử lý ở service.
        [, $query] = $this->stockInService->paginate(array_merge(
            $request->validated(),
            $request->only($this->stockInService->searchableFilters()),
        ));

        return $this->successResponse(
            'Fetched successfully.',
            'list_success',
            self::HTTP_OK,
            $this->paginate($query, defaultSort: ['column' => 'id', 'order' => 'desc'], defaultPerPage: 10),
        );
    }

    /**
     * Chi tiết 1 phiếu nhập kho.
     */
    public function show(BusinessActionRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            'Fetched successfully.',
            'show_success',
            self::HTTP_OK,
            $this->stockInService->show($id, $request->validated()),
        );
    }

    /**
     * Tạo phiếu nhập kho.
     *
     * Thành phần đầu vào:
     * - warehouse_id, supplier_id
     * - items[] gồm product_id, quantity, unit_cost
     *
     * Service sẽ xử lý subtotal, tổng tiền và ledger.
     */
    public function store(StoreStockInRequest $request): JsonResponse
    {
        // Toàn bộ tính tồn và ghi ledger được xử lý ở service; controller chỉ trả response.
        return $this->successResponse(
            'Created successfully.',
            'create_success',
            self::HTTP_OK,
            $this->stockInService->create($request->validated()),
        );
    }

    /**
     * Cập nhật phiếu nhập kho và rebuild ledger nếu cần.
     */
    public function update(UpdateStockInRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            'Updated successfully.',
            'update_success',
            self::HTTP_OK,
            $this->stockInService->update($id, $request->validated()),
        );
    }

    /**
     * Confirm phiếu nhập để cộng tồn.
     */
    public function confirm(TransitionDocumentStatusRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            'Status updated successfully.',
            'update_success',
            self::HTTP_OK,
            $this->stockInService->confirm($id, $request->validated()),
        );
    }

    /**
     * Cancel phiếu nhập để bỏ tác động tồn kho của document.
     */
    public function cancel(TransitionDocumentStatusRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            'Status updated successfully.',
            'update_success',
            self::HTTP_OK,
            $this->stockInService->cancel($id, $request->validated()),
        );
    }
}
