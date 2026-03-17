<?php

namespace App\Services;

use App\Models\Unit;
use App\Repositories\ProductRepository;
use App\Support\BusinessContext;
use Illuminate\Database\Eloquent\Model;

class ProductService extends BaseBusinessCrudService
{
    protected array $with = ['unit'];

    protected array $searchable = ['sku', 'name', 'barcode', 'status'];

    public function __construct(BusinessContext $businessContext, ProductRepository $repository)
    {
        parent::__construct($businessContext);
        $this->repository = $repository;
    }

    protected function payloadForCreate(array $data, int $businessId): array
    {
        $this->assertBelongsToBusiness(Unit::class, $businessId, (int) $data['unit_id'], 'unit_id');

        return array_merge(parent::payloadForCreate($data, $businessId), [
            'product_type' => $data['product_type'] ?? 'simple',
            'track_inventory' => $data['track_inventory'] ?? true,
            'cost_price' => $data['cost_price'] ?? 0,
            'sale_price' => $data['sale_price'] ?? 0,
            'status' => $data['status'] ?? 'active',
        ]);
    }

    protected function payloadForUpdate(array $data, int $businessId, Model $record): array
    {
        if (array_key_exists('unit_id', $data)) {
            $this->assertBelongsToBusiness(Unit::class, $businessId, (int) $data['unit_id'], 'unit_id');
        }

        return parent::payloadForUpdate($data, $businessId, $record);
    }
}
