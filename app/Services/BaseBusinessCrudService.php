<?php
	
	namespace App\Services;
	
	use App\Repositories\BaseBusinessRepository;
	use App\Support\BusinessContext;
	use App\Support\BusinessSequenceGenerator;
	use App\Support\NameSlug;
	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Validation\ValidationException;
	
	abstract class BaseBusinessCrudService
	{
		/**
		 * Lớp nền cho các service CRUD có phạm vi theo business.
		 *
		 * Vai trò của lớp này:
		 * - gom toàn bộ thao tác CRUD dùng chung vào một nơi;
		 * - ép mọi truy vấn đi qua `business_id` để không rò rỉ dữ liệu tenant;
		 * - để service con chỉ cần quan tâm đến luật nghiệp vụ riêng.
		 */
		protected BaseBusinessRepository $repository;
		
		/**
		 * Danh sách quan hệ cần eager load mặc định khi đọc dữ liệu.
		 *
		 * Service con có thể khai báo để controller luôn nhận về dữ liệu đủ ngữ cảnh,
		 * ví dụ: `unit`, `warehouse`, `items.product`.
		 */
		protected array $with = [];
		
		/**
		 * Danh sách cột text có thể dùng cho tìm kiếm cơ bản.
		 *
		 * `paginate()` sẽ tự duyệt mảng này để áp điều kiện `LIKE`,
		 * giúp các resource CRUD đơn giản không phải lặp lại code filter.
		 */
		protected array $searchable = [];

		/**
		 * Các field text cần đi qua cột slug tương ứng khi tìm kiếm.
		 */
		protected array $slugSearchable = [];

		/**
		 * Các field cần chuẩn hóa trước khi tìm kiếm, ví dụ `code`.
		 */
		protected array $normalizedSearchable = [];
		
		/**
		 * @param BusinessContext $businessContext Dung để resolve tenant/business hiện tại
		 */
		public function __construct(protected BusinessContext $businessContext)
		{
		}
		
		/**
		 * Tạo query CRUD đã được gắn business scope.
		 *
		 * @param array<string, mixed> $filters
		 * @return array{0: int, 1: mixed}
		 *
		 * Giá trị trả về:
		 * - index 0: business_id đã resolve
		 * - index 1: query builder từ repository
		 *
		 * Cách dùng:
		 * - controller gọi method này;
		 * - nhận về query đã đúng business hiện tại;
		 * - tiếp tục paginate hoặc transform theo nhu cầu response.
		 */
		public function paginate(array $filters): array
		{
			/**
			 * Trả về cặp [businessId, query] thay vì paginate ngay.
			 *
			 * Cách tách này giúp controller vẫn kiểm soát định dạng response,
			 * còn service chịu trách nhiệm chuẩn hóa query đúng tenant.
			 */
			$businessId = $this->resolveBusinessId($filters);
			$query = $this->repository->queryForBusiness($businessId, $this->with);
			
			// Tìm kiếm text cơ bản cho các resource CRUD; service nào cần rule riêng có thể override.
			$this->applySearchFilters($query, $filters);
			
			return [$businessId, $query];
		}
		
		/**
		 * Áp dụng tìm kiếm text cơ bản cho các resource CRUD.
		 * Service con có thể override nếu cần rule riêng.
		 */
		protected function applySearchFilters($query, array $filters): void
		{
			foreach ($this->searchableFilters() as $field) {
				$value = isset($filters[$field]) ? trim((string) $filters[$field]) : '';

				if ($value === '') {
					continue;
				}

				if (in_array($field, $this->normalizedSearchableFilters(), true)) {
					$this->applyNormalizedSearchFilter($query, $field, $value);
					continue;
				}

				if (in_array($field, $this->slugSearchableFilters(), true)) {
					$this->applySlugSearchFilter($query, $field, $value);
					continue;
				}

				$query->where($field, 'like', '%' . $value . '%');
			}
		}
		
		public function searchableFilters(): array
		{
			// Cho controller biết những field text nào có thể nhận từ request để truyền vào service.
			return $this->searchable;
		}

		protected function slugSearchableFilters(): array
		{
			return $this->slugSearchable;
		}

		protected function normalizedSearchableFilters(): array
		{
			return $this->normalizedSearchable;
		}

		protected function applySlugSearchFilter($query, string $field, string $value): void
		{
			$slug = NameSlug::from($value);

			if ($slug === '') {
				return;
			}

			$query->where($field . '_slug', 'like', '%' . $slug . '%');
		}

		protected function applyNormalizedSearchFilter($query, string $field, string $value): void
		{
			$normalizedValue = str_replace([' ', '-'], '', $value);

			$query->whereRaw(
				'REPLACE(REPLACE(' . $field . ', "-", ""), " ", "") LIKE ?',
				['%' . $normalizedValue . '%']
			);
		}
		
		/**
		 * Lấy chi tiết một bản ghi trong business hiện tại.
		 *
		 * @param int $id
		 * @param array<string, mixed> $data
		 * @return Model
		 */
		public function show(int $id, array $data): Model
		{
			// Mọi thao tác đọc đều phải bị chặn trong đúng phạm vi business.
			$businessId = $this->resolveBusinessId($data);
			
			return $this->repository->findForBusiness($businessId, $id, $this->with);
		}
		
		/**
		 * Tạo bản ghi mới trong business hiện tại.
		 *
		 * @param array<string, mixed> $data
		 * @return Model
		 *
		 * Service con có thể override `payloadForCreate()` để:
		 * - kiểm tra khóa ngoại liên quan;
		 * - gán giá trị mặc định;
		 * - chuẩn hóa payload trước khi lưu.
		 */
		public function create(array $data): Model
		{
			// Repository sẽ là nơi chèn `business_id`, tránh để controller hoặc client tự quyết định.
			$businessId = $this->resolveBusinessId($data);
			
			return $this->repository->createForBusiness($businessId, $this->payloadForCreate($data, $businessId))
				->load($this->with);
		}
		
		/**
		 * Cập nhật bản ghi trong business hiện tại.
		 *
		 * @param int $id
		 * @param array<string, mixed> $data
		 * @return Model
		 *
		 * Service con có thể override `payloadForUpdate()` khi cần xử lý nghiệp vụ riêng.
		 */
		public function update(int $id, array $data): Model
		{
			// Luôn lấy record theo đúng business trước khi cập nhật để tránh ghi đè nhầm tenant khác.
			$businessId = $this->resolveBusinessId($data);
			$record = $this->repository->findForBusiness($businessId, $id, $this->with);
			
			return $this->repository->updateRecord($record, $this->payloadForUpdate($data, $businessId, $record))
				->refresh()
				->load($this->with);
		}
		
		/**
		 * Xóa record trong business hiện tại.
		 *
		 * @param int $id
		 * @param array<string, mixed> $data
		 * @return Model
		 */
		public function delete(int $id, array $data): Model
		{
			// Xóa cũng phải đi qua scope business để không ảnh hưởng dữ liệu ngoài phạm vi hiện tại.
			$businessId = $this->resolveBusinessId($data);
			$record = $this->repository->findForBusiness($businessId, $id, $this->with);
			$this->repository->deleteRecord($record);
			
			return $record;
		}
		
		/**
		 * Resolve `business_id` từ payload đã validate.
		 *
		 * @param array<string, mixed> $data
		 * @return int
		 */
		protected function resolveBusinessId(array $data): int
		{
			// Dồn toàn bộ luật xác định tenant về một chỗ để service con không phải tự xử lý lặp lại.
			return $this->businessContext->resolveBusinessId(isset($data['business_id']) ? (int)$data['business_id'] : null);
		}
		
		/**
		 * Lấy ID người dùng hiện tại.
		 *
		 * @return int|null
		 *
		 * Chủ yếu dùng để ghi `created_by` trên các chứng từ.
		 */
		protected function currentUserId(): ?int
		{
			// Trả về null nếu đang chạy ở ngữ cảnh script/test không có user đăng nhập.
			return $this->businessContext->currentUser()?->id;
		}

		protected function sequenceService(): SequenceService
		{
			return app(SequenceService::class);
		}
		
		/**
		 * Kiểm tra khóa ngoại tham chiếu có thuộc business hiện tại hay không.
		 *
		 * @param class-string $modelClass
		 * @param int $businessId
		 * @param int|null $id
		 * @param string $field Tên field sẽ đưa vào bag lỗi nếu không hợp lệ
		 *
		 * Ví dụ:
		 * - order của business A không được trỏ vào customer của business B
		 */
		protected function assertBelongsToBusiness(string $modelClass, int $businessId, ?int $id, string $field): void
		{
			if ($id === null) {
				return;
			}
			
			// Chặn trường hợp tham chiếu sang dữ liệu của business khác.
			$exists = $modelClass::query()
				->where('business_id', $businessId)
				->whereKey($id)
				->exists();
			
			if (!$exists) {
				throw ValidationException::withMessages([
					$field => 'The selected value is invalid for the current business.',
				]);
			}
		}
		
		/**
		 * Chuẩn hóa payload cho thao tác tạo mới.
		 *
		 * @param array<string, mixed> $data
		 * @param int $businessId
		 * @return array<string, mixed>
		 *
		 * Mặc định chỉ bỏ `business_id` khỏi payload vì repository sẽ tự gắn vào.
		 */
		protected function payloadForCreate(array $data, int $businessId): array
		{
			// Không cho phép client quyết định `business_id` thực sự được ghi xuống DB.
			unset($data['business_id']);
			
			return $data;
		}
		
		/**
		 * Chuẩn hóa payload cho thao tác cập nhật.
		 *
		 * @param array<string, mixed> $data
		 * @param int $businessId
		 * @param Model $record
		 * @return array<string, mixed>
		 */
		protected function payloadForUpdate(array $data, int $businessId, Model $record): array
		{
			// Giữ cùng nguyên tắc với create: không cho phép sửa tay `business_id`.
			unset($data['business_id']);
			
			return $data;
		}
		
		/**
		 * Sinh mã chứng từ tăng dần trong phạm vi từng business.
		 *
		 * @param class-string $modelClass
		 * @param int $businessId
		 * @param string $numberColumn Cột lưu mã chứng từ, ví dụ `order_no`
		 * @param string $prefix Tiền tố, ví dụ `ORD`
		 * @return string
		 *
		 * Ví dụ:
		 * - prefix = ORD
		 * - sequence = 12
		 * => ORD-0012
		 */
		protected function nextDocumentNumber(string $modelClass, int $businessId, string $numberColumn, string $prefix): string
		{
			return $this->sequenceService()->nextScopedCode(
				$businessId,
				BusinessSequenceGenerator::scopeFor($modelClass, $numberColumn),
				$prefix,
				4,
			);
		}
	}
