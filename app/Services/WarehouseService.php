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
    protected array $searchable = ['code', 'name'];

    /**
     * @param  BusinessContext  $businessContext
     * @param  WarehouseRepository  $repository
     */
    public function __construct(BusinessContext $businessContext, WarehouseRepository $repository)
    {
        parent::__construct($businessContext);
        $this->repository = $repository;
    }

    public function paginate(array $filters): array
    {
        [$businessId, $query] = parent::paginate($filters);

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return [$businessId, $query];
    }

    /**
     * Chuẩn hóa payload khi tạo kho.
     *
     * `name` vẫn là dữ liệu do người dùng đặt, còn `code`
     * sẽ được model tự sinh trước khi lưu.
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
