<?php

namespace App\Repositories;

use App\Models\Customer;

class CustomerRepository extends BaseBusinessRepository
{
    protected function modelClass(): string
    {
        return Customer::class;
    }
}
