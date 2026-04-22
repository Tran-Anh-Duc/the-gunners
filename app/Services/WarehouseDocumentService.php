<?php
	
namespace App\Services;
	
	
use App\Models\WarehouseDocument;
use App\Repositories\WarehouseDocumentDetailRepository;
use App\Repositories\WarehouseDocumentRepository;
use App\Support\BusinessContext;
use App\Traits\ApiResponse;
use Carbon\Carbon;
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
		private readonly WarehouseDocumentRepository       $warehouseDocumentRepository,
		private readonly WarehouseDocumentDetailRepository $warehouseDocumentDetailRepository,
		protected BusinessContext                          $businessContext,
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
	 * @param int $id
	 * @param array<string, mixed> $data
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
			
			$prepared = $this->prepareDocumentPayloadAndRows($data, $businessId, false);
			
			$document = $this->warehouseDocumentRepository->createForBusiness(
				$businessId,
				$prepared['payload']
			);
			
			if (!empty($prepared['rows'])) {
				$rows = $this->attachDocumentIdToRows($prepared['rows'], $document->id);
				
				$this->warehouseDocumentDetailRepository->insertRows($rows);
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
			
			$prepared = $this->prepareDocumentPayloadAndRows($data, $businessId, true);
			
			$document = $this->warehouseDocumentRepository->updateForBusiness(
				$businessId,
				$prepared['payload'],
				$id
			);
			
			if ($prepared['rows'] !== null) {
				$rows = $this->attachDocumentIdToRows($prepared['rows'], $document->id);
				$this->warehouseDocumentDetailRepository->replaceRowsForDocument(
					$document->id,
					$rows
				);
			}
			
			
			return $document->load($this->with);
		});
	}
	
	protected function payloadForSave(array $data, int $businessId, bool $isUpdate = false): array
	{
		$now = Carbon::now();
		
		if (!$isUpdate) {
			$status = $data['status'] ?? 'draft';
			
			$payload = [
				'business_id' => $businessId,
				'document_type' => $data['document_type'] ?? null,
				'warehouse_id' => $data['warehouse_id'] ?? null,
				'document_date' => $data['document_date'] ?? null,
				'status' => $status,
				'reference_code' => $data['reference_code'] ?? null,
				'note' => $data['note'] ?? null,
				
				'subtotal_amount' => $data['subtotal_amount'] ?? 0,
				'tax_amount' => $data['tax_amount'] ?? 0,
				'total_amount' => $data['total_amount'] ?? 0,
				
				'created_by' => auth()->id(),
			];
			
			if ($status === 'confirmed') {
				$payload['approved_by'] = auth()->id();
				$payload['approved_at'] = $now;
			} else {
				$payload['approved_by'] = null;
				$payload['approved_at'] = null;
			}
			
			return $payload;
		}
		
		$payload = [
			'updated_by' => auth()->id(),
		];
		
		$fields = [
			'document_type',
			'warehouse_id',
			'document_date',
			'status',
			'reference_code',
			'note',
			'subtotal_amount',
			'tax_amount',
			'total_amount',
		];
		
		foreach ($fields as $field) {
			if (array_key_exists($field, $data)) {
				$payload[$field] = $data[$field];
			}
		}
		
		if (array_key_exists('status', $data)) {
			if ($data['status'] === 'confirmed') {
				$payload['approved_by'] = auth()->id();
				$payload['approved_at'] = $now;
			} else {
				$payload['approved_by'] = null;
				$payload['approved_at'] = null;
			}
		}
		
		return $payload;
	}
	
	
	protected function calculateDetailAmounts(
		float $quantity,
		float $unitPrice,
		float $taxRate,
		bool  $priceIncludesTax = false
	): array
	{
		$quantity = (float)$quantity;
		$unitPrice = (float)$unitPrice;
		$taxRate = (float)$taxRate;
		
		if ($priceIncludesTax) {
			// Giá đã bao gồm thuế
			$total = round($quantity * $unitPrice, 2);
			
			$subtotal = round($total / (1 + $taxRate / 100), 2);
			$tax = round($total - $subtotal, 2);
		} else {
			// Giá chưa bao gồm thuế
			$subtotal = round($quantity * $unitPrice, 2);
			
			$tax = round($subtotal * $taxRate / 100, 2);
			$total = round($subtotal + $tax, 2);
		}
		
		return [
			'subtotal' => $subtotal,
			'tax_price' => $tax,
			'total_price' => $total,
		];
	}
	
	protected function prepareDetailRowsAndTotals(array $details, bool $priceIncludesTax = false): array
	{
		$now = now();
		$rows = [];
		
		$subtotalAmount = 0;
		$taxAmount = 0;
		$totalAmount = 0;
		
		foreach ($details as $detail) {
			$amounts = $this->calculateDetailAmounts(
				(float)($detail['quantity'] ?? 0),
				(float)($detail['unit_price'] ?? 0),
				(float)($detail['tax_rate'] ?? 0),
				$priceIncludesTax
			);
			
			$row = [
				'product_id' => $detail['product_id'] ?? null,
				'product_name' => $detail['product_name'] ?? null,
				'unit_id' => $detail['unit_id'] ?? null,
				'unit_name' => $detail['unit_name'] ?? null,
				'quantity' => (float)($detail['quantity'] ?? 0),
				'unit_price' => (float)($detail['unit_price'] ?? 0),
				'subtotal' => $amounts['subtotal'],
				'tax_rate' => (float)($detail['tax_rate'] ?? 0),
				'tax_price' => $amounts['tax_price'],
				'total_price' => $amounts['total_price'],
				'note' => $detail['note'] ?? null,
				'created_at' => $now,
				'updated_at' => $now,
			];
			
			$subtotalAmount += $row['subtotal'];
			$taxAmount += $row['tax_price'];
			$totalAmount += $row['total_price'];
			
			$rows[] = $row;
		}
		
		return [
			'rows' => $rows,
			'totals' => [
				'subtotal_amount' => round($subtotalAmount, 2),
				'tax_amount' => round($taxAmount, 2),
				'total_amount' => round($totalAmount, 2),
			],
		];
	}
	
	protected function prepareDocumentPayloadAndRows(array $data, int $businessId, bool $isUpdate = false): array
	{
		$hasDetails = array_key_exists('details', $data);
		$rows = null;
		$isPriceIncludesTax = (bool)($data['is_price_includes_tax'] ?? false);
		
		$payloadData = $data;
		
		if ($hasDetails) {
			$details = $data['details'];
			$prepared = $this->prepareDetailRowsAndTotals($details, $isPriceIncludesTax);
			$rows = $prepared['rows'];
			unset($payloadData['details']);
			$payloadData = array_merge($payloadData, $prepared['totals']);
		}
		
		return [
			'payload' => $this->payloadForSave($payloadData, $businessId, $isUpdate),
			'rows' => $rows,
		];
	}
	
	protected function attachDocumentIdToRows(array $rows, int $documentId): array
	{
		return array_map(function ($row) use ($documentId) {
			$row['warehouse_document_id'] = $documentId;
			return $row;
		}, $rows);
	}
		
}
