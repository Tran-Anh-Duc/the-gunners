<?php

namespace App\Services;

use App\Repositories\CustomerRepository;
use App\Support\BusinessContext;

class CustomerService extends BaseBusinessCrudService
{
    protected array $searchable = ['name', 'phone', 'email'];

    public function __construct(BusinessContext $businessContext, CustomerRepository $repository)
    {
        parent::__construct($businessContext);
        $this->repository = $repository;
    }
}
