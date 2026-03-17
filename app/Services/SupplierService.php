<?php

namespace App\Services;

use App\Repositories\SupplierRepository;
use App\Support\BusinessContext;

class SupplierService extends BaseBusinessCrudService
{
    protected array $searchable = ['name', 'contact_name', 'phone', 'email'];

    public function __construct(BusinessContext $businessContext, SupplierRepository $repository)
    {
        parent::__construct($businessContext);
        $this->repository = $repository;
    }
}
