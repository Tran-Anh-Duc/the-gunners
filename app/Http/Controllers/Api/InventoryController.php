<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\InventoryIndexRequest;
use App\Services\InventoryService;
use App\Traits\HasApiPagination;
use Illuminate\Http\JsonResponse;

/**
 * Controller doc tồn kho hiện tại.
 *
 * Endpoint này đọc từ `current_stocks` thay vì quét trực tiếp `inventory_movements`,
 * nhờ đó màn hình tồn kho lấy dữ liệu nhanh hơn và vẫn bám theo số liệu đã được ledger tổng hợp.
 */
class InventoryController extends ApiController
{
    use HasApiPagination;

    public function __construct(private readonly InventoryService $inventoryService)
    {
    }

    /**
     * Danh sách tồn kho hiện tại.
     *
     * Thành phần đầu vào:
     * - warehouse_id
     * - product_id
     * - product_name
     * - sku
     *
     * Cách xử lý:
     * - InventoryService::paginate()
     * - service sẽ áp filter theo kho, sản phẩm và business hiện tại
     *
     * Kết quả trả ra:
     * - danh sách current_stocks đã paginated
     */
    public function index(InventoryIndexRequest $request): JsonResponse
    {
        // Controller chỉ điều phối; toàn bộ logic lọc dữ liệu tồn kho nằm ở service.
        [, $query] = $this->inventoryService->paginate($request->validated());

        return $this->successResponse(
            'Fetched successfully.',
            'list_success',
            self::HTTP_OK,
            $this->paginate($query, defaultSort: ['column' => 'id', 'order' => 'desc'], defaultPerPage: 20),
        );
    }
}
