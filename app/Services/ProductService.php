<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Unit;
use App\Repositories\ProductRepository;
use App\Support\BusinessContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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
    protected array $with = ['unit', 'category'];

    protected array $searchable = ['sku', 'name', 'barcode'];

    protected array $slugSearchable = ['name'];

    /**
     * @param  BusinessContext  $businessContext
     * @param  ProductRepository  $repository
     */
    public function __construct(BusinessContext $businessContext, ProductRepository $repository)
    {
        parent::__construct($businessContext);
        $this->repository = $repository;
    }

    public function create(array $data): Model
    {
        $businessId = $this->resolveBusinessId($data);

        return DB::transaction(function () use ($data, $businessId) {
            $payload = $this->payloadForCreate($data, $businessId);

            if (empty($payload['sku'])) {
                $payload['sku'] = $this->sequenceService()->nextProductSku($businessId);
            }

            return $this->repository->createForBusiness($businessId, $payload)
                ->load($this->with);
        });
    }

    public function paginate(array $filters): array
    {
        [$businessId, $query] = parent::paginate($filters);

        if (! empty($filters['category_id'])) {
            $query->where('category_id', (int) $filters['category_id']);
        }

        if (! empty($filters['unit_id'])) {
            $query->where('unit_id', (int) $filters['unit_id']);
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOL));
        }

        return [$businessId, $query];
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
     * - bổ sung default cho `product_type`, `track_inventory`, `cost_price`, `sale_price`, `is_active`.
     */
    protected function payloadForCreate(array $data, int $businessId): array
    {
        // Sản phẩm bắt buộc phải tham chiếu tới đơn vị tính cùng tenant.
        $this->assertBelongsToBusiness(Unit::class, $businessId, (int) $data['unit_id'], 'unit_id');
        $this->assertBelongsToBusiness(Category::class, $businessId, isset($data['category_id']) ? (int) $data['category_id'] : null, 'category_id');

        $sku = isset($data['sku']) ? trim((string) $data['sku']) : null;

        return array_merge(parent::payloadForCreate($data, $businessId), [
            'sku' => $sku !== '' ? $sku : null,
            'product_type' => $data['product_type'] ?? 'simple',
            'track_inventory' => $data['track_inventory'] ?? true,
            'cost_price' => $data['cost_price'] ?? 0,
            'sale_price' => $data['sale_price'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
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
        unset($data['sku']);

        // Chỉ kiểm tra lại `unit_id` khi request thực sự muốn thay đổi đơn vị tính.
        if (array_key_exists('unit_id', $data)) {
            $this->assertBelongsToBusiness(Unit::class, $businessId, (int) $data['unit_id'], 'unit_id');
        }

        if (array_key_exists('category_id', $data)) {
            $this->assertBelongsToBusiness(Category::class, $businessId, $data['category_id'] !== null ? (int) $data['category_id'] : null, 'category_id');
        }

        return parent::payloadForUpdate($data, $businessId, $record);
    }
}
