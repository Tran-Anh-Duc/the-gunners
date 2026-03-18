<?php

namespace App\Repositories;

use App\Models\Customer;

/**
 * Repository customer theo business.
 *
 * Hiện tại chưa có custom query,
 * nhưng vẫn tách riêng để giữ layering nhất quán
 * và sẵn sàng cho việc thêm logic query sau này.
 */
class CustomerRepository extends BaseBusinessRepository
{
    protected function modelClass(): string
    {
        return Customer::class;
    }
}
