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

    /**
     * Chuẩn hóa payload khi tạo đơn vị tính.
     *
     * `code` sẽ do model tự sinh; service chỉ cần đảm bảo các default
     * hiển thị nhất quán ngay ở response trả về sau khi tạo.
     *
     * @param  array<string, mixed>  $data
     * @param  int  $businessId
     * @return array<string, mixed>
     */
    protected function payloadForCreate(array $data, int $businessId): array
    {
        return array_merge(parent::payloadForCreate($data, $businessId), [
            'is_active' => $data['is_active'] ?? true,
        ]);
    }
}
