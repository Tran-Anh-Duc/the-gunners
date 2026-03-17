<?php

namespace App\Services;

use App\Repositories\InventoryRepository;
use App\Support\BusinessContext;

class InventoryService extends BaseBusinessCrudService
{
    protected array $with = ['warehouse', 'product'];

    public function __construct(BusinessContext $businessContext, InventoryRepository $inventoryRepository)
    {
        parent::__construct($businessContext);
        $this->repository = $inventoryRepository;
    }

    public function paginate(array $filters): array
    {
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
