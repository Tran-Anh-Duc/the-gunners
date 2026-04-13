<?php

namespace App\Services;

use App\Repositories\WarehouseDocumentDetailRepository;
use App\Repositories\WarehouseDocumentRepository;
use App\Repositories\WarehouseRepository;
use App\Support\BusinessContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class WarehouseDocumentService
{
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
		private readonly BusinessContext             $businessContext,
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
	
}
