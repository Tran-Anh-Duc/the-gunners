<?php

namespace App\Services;

use App\Repositories\CustomerRepository;
use App\Support\BusinessContext;

/**
 * Service CRUD khách hàng.
 *
 * Ở giai đoạn MVP, customer chưa có nghiệp vụ phức tạp.
 * Service này chủ yếu kế thừa flow chuẩn từ `BaseBusinessCrudService`
 * để xử lý CRUD theo business scope một cách thống nhất.
 */
class CustomerService extends BaseBusinessCrudService
{
    /**
     * Các field hỗ trợ tìm kiếm text ở màn hình danh sách khách hàng.
     */
    protected array $searchable = ['name', 'phone', 'email'];

    /**
     * @param  BusinessContext  $businessContext
     * @param  CustomerRepository  $repository
     */
    public function __construct(BusinessContext $businessContext, CustomerRepository $repository)
    {
        parent::__construct($businessContext);
        $this->repository = $repository;
    }
}
