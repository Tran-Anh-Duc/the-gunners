<?php

namespace App\Services;

use App\Repositories\InventoryRepository;
use App\Support\BusinessContext;

/**
 * Service doc tồn kho hiện tại.
 *
 * Service này làm việc với `current_stocks`, tức bảng tổng hợp tồn hiện tại.
 * Cách truy vấn này phù hợp cho màn hình list tồn kho nhanh,
 * thay vì phải quét toàn bộ ledger mỗi lần đọc dữ liệu.
 */
class InventoryService extends BaseBusinessCrudService
{
    protected array $with = ['warehouse', 'product'];

    /**
     * @param  BusinessContext  $businessContext
     * @param  InventoryRepository  $inventoryRepository
     */
    public function __construct(BusinessContext $businessContext, InventoryRepository $inventoryRepository)
    {
        parent::__construct($businessContext);
        $this->repository = $inventoryRepository;
    }

    /**
     * Tạo query tồn kho hiện tại với filter nghiệp vụ.
     *
     * @param  array<string, mixed>  $filters
     * @return array{0: int, 1: mixed}
     *
     * Hỗ trợ filter:
     * - warehouse_id
     * - product_id
     * - product_name
     * - sku
     */
    public function paginate(array $filters): array
    {
        /**
         * Override `paginate()` để thêm filter nghiệp vụ đặc thù cho tồn kho.
         *
         * Các filter như warehouse, product hoặc SKU đều được xử lý ở đây
         * để controller không phải biết chi tiết cách query current stock.
         */
        [$businessId, $query] = parent::paginate($filters);

        if (! empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', (int) $filters['warehouse_id']);
        }

        if (! empty($filters['product_id'])) {
            $query->where('product_id', (int) $filters['product_id']);
        }

        if (! empty($filters['product_name'])) {
            $query->whereHas('product', function ($productQuery) use ($filters) {
                $productQuery->where('name', 'like', '%'.$filters['product_name'].'%');
            });
        }

        if (! empty($filters['sku'])) {
            $query->whereHas('product', function ($productQuery) use ($filters) {
                $productQuery->where('sku', 'like', '%'.$filters['sku'].'%');
            });
        }

        return [$businessId, $query];
    }
}
