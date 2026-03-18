<?php

namespace App\Services;

use App\Models\Unit;
use App\Repositories\ProductRepository;
use App\Support\BusinessContext;
use Illuminate\Database\Eloquent\Model;

/**
 * Service CRUD sản phẩm.
 *
 * Ngoài flow CRUD chung của lớp nền, service này phụ trách:
 * - kiểm tra `unit_id` có thuộc cùng business hay không;
 * - gán các giá trị mặc định cho cấu hình sản phẩm;
 * - bảo đảm dữ liệu sản phẩm luôn nhất quán trước khi lưu xuống DB.
 */
class ProductService extends BaseBusinessCrudService
{
    protected array $with = ['unit'];

    protected array $searchable = ['sku', 'name', 'barcode', 'status'];

    /**
     * @param  BusinessContext  $businessContext
     * @param  ProductRepository  $repository
     */
    public function __construct(BusinessContext $businessContext, ProductRepository $repository)
    {
        parent::__construct($businessContext);
        $this->repository = $repository;
    }

    /**
     * Chuẩn hóa payload khi tạo sản phẩm.
     *
     * @param  array<string, mixed>  $data
     * @param  int  $businessId
     * @return array<string, mixed>
     *
     * Method này sẽ:
     * - kiểm tra `unit_id` thuộc business hiện tại;
     * - bổ sung default cho `product_type`, `track_inventory`, `cost_price`, `sale_price`, `status`.
     */
    protected function payloadForCreate(array $data, int $businessId): array
    {
        // Sản phẩm bắt buộc phải tham chiếu tới đơn vị tính cùng tenant.
        $this->assertBelongsToBusiness(Unit::class, $businessId, (int) $data['unit_id'], 'unit_id');

        return array_merge(parent::payloadForCreate($data, $businessId), [
            'product_type' => $data['product_type'] ?? 'simple',
            'track_inventory' => $data['track_inventory'] ?? true,
            'cost_price' => $data['cost_price'] ?? 0,
            'sale_price' => $data['sale_price'] ?? 0,
            'status' => $data['status'] ?? 'active',
        ]);
    }

    /**
     * Chuẩn hóa payload khi cập nhật sản phẩm.
     *
     * @param  array<string, mixed>  $data
     * @param  int  $businessId
     * @param  Model  $record
     * @return array<string, mixed>
     */
    protected function payloadForUpdate(array $data, int $businessId, Model $record): array
    {
        // Chỉ kiểm tra lại `unit_id` khi request thực sự muốn thay đổi đơn vị tính.
        if (array_key_exists('unit_id', $data)) {
            $this->assertBelongsToBusiness(Unit::class, $businessId, (int) $data['unit_id'], 'unit_id');
        }

        return parent::payloadForUpdate($data, $businessId, $record);
    }
}
