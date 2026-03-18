<?php

namespace App\Services;

use App\Repositories\UnitRepository;
use App\Support\BusinessContext;

/**
 * Service CRUD đơn vị tính.
 *
 * Unit được scope theo business để tránh trùng mã hoặc tên giữa các shop,
 * đồng thời giữ sẵn một điểm mở rộng nếu sau này bổ sung quy đổi đơn vị.
 */
class UnitService extends BaseBusinessCrudService
{
    /**
     * Các field text có thể dùng để tìm kiếm danh sách đơn vị tính.
     */
    protected array $searchable = ['code', 'name'];

    /**
     * @param  BusinessContext  $businessContext
     * @param  UnitRepository  $repository
     */
    public function __construct(BusinessContext $businessContext, UnitRepository $repository)
    {
        parent::__construct($businessContext);
        $this->repository = $repository;
    }
}
