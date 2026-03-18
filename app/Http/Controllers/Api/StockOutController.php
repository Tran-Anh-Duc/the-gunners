<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\BusinessActionRequest;
use App\Http\Requests\BusinessIndexRequest;
use App\Http\Requests\StoreStockOutRequest;
use App\Http\Requests\TransitionDocumentStatusRequest;
use App\Http\Requests\UpdateStockOutRequest;
use App\Services\StockOutService;
use App\Traits\HasApiPagination;
use Illuminate\Http\JsonResponse;

/**
 * Controller phiếu xuất kho.
 *
 * Xuất kho ảnh hưởng trực tiếp tới tồn kho và giá vốn,
 * nên controller chủ động giữ mỏng để service xử lý tập trung.
 */
class StockOutController extends ApiController
{
    use HasApiPagination;

    public function __construct(private readonly StockOutService $stockOutService)
    {
    }

    /**
     * Danh sách phiếu xuất kho.
     *
     * Query sẽ được service áp business scope và filter theo số phiếu, trạng thái,
     * loại chứng từ hoặc mã tham chiếu trước khi paginate.
     */
    public function index(BusinessIndexRequest $request): JsonResponse
    {
        [, $query] = $this->stockOutService->paginate(array_merge(
            $request->validated(),
            $request->only($this->stockOutService->searchableFilters()),
        ));

        return $this->successResponse(
            'Fetched successfully.',
            'list_success',
            self::HTTP_OK,
            $this->paginate($query, defaultSort: ['column' => 'id', 'order' => 'desc'], defaultPerPage: 10),
        );
    }

    /**
     * Chi tiết 1 phiếu xuất kho.
     */
    public function show(BusinessActionRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            'Fetched successfully.',
            'show_success',
            self::HTTP_OK,
            $this->stockOutService->show($id, $request->validated()),
        );
    }

    /**
     * Tạo phiếu xuất kho.
     *
     * Service sẽ:
     * - kiểm tra warehouse, order, customer;
     * - dựng snapshot item;
     * - sync ledger để trừ tồn khi document được confirm.
     */
    public function store(StoreStockOutRequest $request): JsonResponse
    {
        return $this->successResponse(
            'Created successfully.',
            'create_success',
            self::HTTP_OK,
            $this->stockOutService->create($request->validated()),
        );
    }

    /**
     * Cập nhật phiếu xuất kho.
     *
     * Nếu item thay đổi thì ledger cũng sẽ được rebuild để giá vốn và tồn kho không bị lệch.
     */
    public function update(UpdateStockOutRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            'Updated successfully.',
            'update_success',
            self::HTTP_OK,
            $this->stockOutService->update($id, $request->validated()),
        );
    }

    /**
     * Confirm phiếu xuất kho để trừ tồn và tính giá vốn.
     */
    public function confirm(TransitionDocumentStatusRequest $request, int $id): JsonResponse
    {
        // Đây là điểm kích hoạt ledger trừ tồn và chốt giá vốn theo moving average.
        return $this->successResponse(
            'Status updated successfully.',
            'update_success',
            self::HTTP_OK,
            $this->stockOutService->confirm($id, $request->validated()),
        );
    }

    /**
     * Cancel phiếu xuất kho để gỡ tác động tồn kho của document.
     */
    public function cancel(TransitionDocumentStatusRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            'Status updated successfully.',
            'update_success',
            self::HTTP_OK,
            $this->stockOutService->cancel($id, $request->validated()),
        );
    }
}
