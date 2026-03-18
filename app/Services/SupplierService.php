<?php

namespace App\Services;

use App\Repositories\SupplierRepository;
use App\Support\BusinessContext;

/**
 * Service CRUD nhà cung cấp.
 *
 * Supplier hiện tại là master data tương đối đơn giản,
 * nhưng vẫn tách service riêng để sau này mở rộng công nợ phải trả,
 * tuổi nợ hoặc lịch sử giao dịch mua hàng.
 */
class SupplierService extends BaseBusinessCrudService
{
    /**
     * Các field text có thể dùng để tìm kiếm danh sách nhà cung cấp.
     */
    protected array $searchable = ['name', 'contact_name', 'phone', 'email'];

    /**
     * @param  BusinessContext  $businessContext
     * @param  SupplierRepository  $repository
     */
    public function __construct(BusinessContext $businessContext, SupplierRepository $repository)
    {
        parent::__construct($businessContext);
        $this->repository = $repository;
    }
}
