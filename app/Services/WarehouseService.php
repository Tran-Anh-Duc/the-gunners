<?php

namespace App\Services;

use App\Repositories\WarehouseRepository;
use App\Support\BusinessContext;

/**
 * Service CRUD kho.
 *
 * Kho là một chiều bắt buộc của inventory ledger.
 * Dù hiện tại chưa có nghiệp vụ quá phức tạp, việc tách service riêng
 * giúp sau này dễ bổ sung rule về kho mặc định, khóa kho hoặc phân quyền theo kho.
 */
class WarehouseService extends BaseBusinessCrudService
{
    /**
     * Các field hỗ trợ tìm kiếm ở màn hình danh sách kho.
     */
    protected array $searchable = ['code', 'name', 'status'];

    /**
     * @param  BusinessContext  $businessContext
     * @param  WarehouseRepository  $repository
     */
    public function __construct(BusinessContext $businessContext, WarehouseRepository $repository)
    {
        parent::__construct($businessContext);
        $this->repository = $repository;
    }
}
