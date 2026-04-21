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
				
				$details = $data['details'] ?? [];
				$isPriceIncludesTax = (bool)($data['is_price_includes_tax'] ?? false);
				
				$prepared = $this->prepareDetailRowsAndTotals($details, $isPriceIncludesTax);
				
				$payloadData = $data;
				unset($payloadData['details']);
				
				$payloadData = array_merge($payloadData, $prepared['totals']);
				
				$payload = $this->payloadForCreate($payloadData, $businessId);
				
				$document = $this->warehouseDocumentRepository->createForBusiness($businessId, $payload);
				
				if (!empty($prepared['rows'])) {
					$rows = array_map(function ($row) use ($document) {
						$row['warehouse_document_id'] = $document->id;
						return $row;
					}, $prepared['rows']);
					
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
				$details = $data['details'] ?? [];
				$isPriceIncludesTax = (bool)($data['is_price_includes_tax'] ?? false);
				$prepared = $this->prepareDetailRowsAndTotals($details, $isPriceIncludesTax);
				$payloadData = $data;
				unset($payloadData['details']);
				$payloadData = array_merge($payloadData, $prepared['totals']);
				$payload = $this->payloadForCreate($payloadData, $businessId);
				
				$document = $this->warehouseDocumentRepository->updateForBusiness(
					$businessId, $payload, $id
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
			$now = Carbon::now();
			$status = $data['status'] ?? 'draft';
			
			return [
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
				'approved_by' => $status === 'confirmed' ? auth()->id() : null,
				'approved_at' => $status === 'confirmed' ? $now : null,
				'created_by' => auth()->id(),
			];
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
		
	}
