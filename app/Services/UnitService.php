<?php

namespace App\Services;

use App\Repositories\UnitRepository;
use App\Support\BusinessContext;

class UnitService extends BaseBusinessCrudService
{
    protected array $searchable = ['code', 'name'];

    public function __construct(BusinessContext $businessContext, UnitRepository $repository)
    {
        parent::__construct($businessContext);
        $this->repository = $repository;
    }
}
