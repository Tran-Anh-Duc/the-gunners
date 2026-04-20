<?php

namespace App\Services;


use App\Models\WarehouseDocument;
use App\Repositories\WarehouseDocumentDetailRepository;
use App\Repositories\WarehouseDocumentRepository;
use App\Support\BusinessContext;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class WarehouseDocumentService extends BaseBusinessCrudService
{
	use ApiResponse;
	protected array $with = [
		'business',
		'warehouse',
		'creator',
		'updater',
		'approver',
		'details.product',
		'details.unit',
	];
	
	protected array $searchable = [
		'keyword',
		'document_type',
		'status',
		'warehouse_id',
		'created_by',
		'document_date_from',
		'document_date_to',
	];
	
	
	public function __construct(
		private readonly WarehouseDocumentRepository $warehouseDocumentRepository,
		private readonly WarehouseDocumentDetailRepository $warehouseDocumentDetailRepository,
		protected BusinessContext                    $businessContext,
	)
	{
	}
	
	/**
	 * Tạo query danh sách warehouse trong business.
	 *
	 * @param array<string, mixed> $filters
	 * @return Builder
	 *
	 * Query trả về chưa paginate vì controller sẽ quyết định cách phân trang.
	 * Các filter membership như role hoặc status được đẩy xuống repository.
	 */
	public function listQuery(array $filters): Builder
	{
		$businessId = $this->businessContext->resolveBusinessId($filters['business_id'] ?? null);
		return $data = $this->warehouseDocumentRepository->queryForBusiness($businessId, $filters);
	}
	
	/**
     * Lấy thông tin chi tiết một warehouse trong business hiện tại.
     *
     * @param  int  $id
     * @param  array<string, mixed>  $data
     * @return Model
     */
	public function show(int $id, array $data): Model
	{
		$businessId = $this->businessContext->resolveBusinessId();
		return $this->warehouseDocumentRepository->findForBusinessOrFail($id, $businessId);
	}
	
	public function create(array $data): Model
	{
		return DB::transaction(function () use ($data) {
			$businessId = $this->resolveBusinessId($data);
			
			$details = $data['details'] ?? [];
			
			$payload = $this->payloadForCreate(
				array_diff_key($data, ['details' => true]),
				$businessId
			);
			
			$document = $this->warehouseDocumentRepository->createForBusiness($businessId, $payload);
			
			if (!empty($details)) {
				$this->warehouseDocumentDetailRepository->createManyForDocument(
					$document->id,
					$businessId,
					$details
				);
			}
			
			return $document->load($this->with);
		});
	}
	
	/**
	 * @param int $id
	 * @param array $data
	 * @return Model
	 */
	public function update(int $id, array $data): Model
	{
		return DB::transaction(function () use ($id, $data) {
			$businessId = $this->resolveBusinessId($data);
			$details = $data['details'] ?? [];
			
			$payload = $this->payloadForCreate(
				array_diff_key($data, ['details' => true]),
				$businessId
			);
			
			$document = $this->warehouseDocumentRepository->updateForBusiness(
				$businessId,
				$payload,
				$id
			);
			
			$this->warehouseDocumentDetailRepository->updateAndCreateManyForDocument(
				$document->id,
				$businessId,
				$details
			);
			
			return $document->load($this->with);
		});
	}
	
	protected function payloadForCreate(array $data, int $businessId): array
	{
		return [
			'business_id' => $businessId,
			'document_type' => $data['document_type'] ?? null,
			'warehouse_id' => $data['warehouse_id'] ?? null,
			'document_date' => $data['document_date'] ?? null,
			'status' => $data['status'] ?? 'draft',
			'reference_code' => $data['reference_code'] ?? null,
			'note' => $data['note'] ?? null,
			'approved_by' => $data['approved_by'] ?? null,
			'approved_at' => $data['approved_at'] ?? null,
			'created_by' => auth()->id(),
		];
	}
	
}
