<?php

namespace App\Services;

use App\Repositories\WarehouseRepository;
use App\Support\BusinessContext;

class WarehouseService extends BaseBusinessCrudService
{
    protected array $searchable = ['code', 'name', 'status'];

    public function __construct(BusinessContext $businessContext, WarehouseRepository $repository)
    {
        parent::__construct($businessContext);
        $this->repository = $repository;
    }
}
