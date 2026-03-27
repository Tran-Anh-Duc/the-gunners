<?php

namespace App\Services;

use App\Repositories\UserRepository;
use App\Support\BusinessContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService
{
    /**
     * Service quản lý user và membership trong business.
     *
     * Điểm quan trọng của service này là tách rõ:
     * - dữ liệu tài khoản hệ thống trong bảng `users`;
     * - dữ liệu vai trò và trạng thái theo business trong bảng `business_users`.
     */
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly BusinessContext $businessContext,
    ) {
    }

    /**
     * Tạo query danh sách user trong business.
     *
     * @param  array<string, mixed>  $filters
     * @return Builder
     *
     * Query trả về chưa paginate vì controller sẽ quyết định cách phân trang.
     * Các filter membership như role hoặc status được đẩy xuống repository.
     */
    public function listQuery(array $filters): Builder
    {
        /**
         * Trả query user đã scope theo business.
         *
         * Controller sẽ dùng query này để paginate,
         * còn phần filter tenant và membership phải được xử lý ở service/repository trước.
         */
        $businessId = $this->resolveBusinessId($filters);

        // Danh sách user luôn bị giới hạn trong business hiện tại.
        return $this->userRepository->queryForBusiness($businessId, $filters);
    }

    /**
     * Lấy thông tin chi tiết một user trong business hiện tại.
     *
     * @param  int  $id
     * @param  array<string, mixed>  $data
     * @return Model
     */
    public function show(int $id, array $data): Model
    {
        // Chỉ cho xem user nếu user đó thực sự thuộc business đang thao tác.
        $businessId = $this->resolveBusinessId($data);

        return $this->loadUserRelations(
            $this->userRepository->findForBusinessOrFail($businessId, $id),
            $businessId,
        );
    }

    /**
     * Tạo user mới trong business hiện tại.
     *
     * @param  array<string, mixed>  $data
     * @return Model
     *
     * Luồng xử lý:
     * 1. resolve business_id
     * 2. tách field của bảng users
     * 3. hash password
     * 4. tạo user hệ thống
     * 5. tạo membership trong business
     * 6. load lại relation để trả response
     */
    public function create(array $data): Model
    {
        /**
         * Tạo user mới theo hai lớp dữ liệu:
         * - `user`: thông tin tài khoản hệ thống;
         * - `membership`: vai trò của user trong business hiện tại.
         */
        return DB::transaction(function () use ($data) {
            $businessId = $this->resolveBusinessId($data);
            $userData = $this->extractUserAttributes($data, true);
            $userData['password'] = Hash::make($userData['password']);
            $userData['is_active'] = $userData['is_active'] ?? true;

            $user = $this->userRepository->create($userData);
            // User được tạo ở hệ thống trước, sau đó mới gắn membership vào business hiện tại.
            $this->userRepository->createMembership([
                'business_id' => $businessId,
                'user_id' => $user->id,
                'role' => $data['role'] ?? 'staff',
                'status' => $data['membership_status'] ?? 'active',
                'is_owner' => (bool) ($data['is_owner'] ?? false),
                'joined_at' => now(),
            ]);

            return $this->loadUserRelations($user, $businessId);
        });
    }

    /**
     * Cập nhật user.
     *
     * @param  int  $id
     * @param  array<string, mixed>  $data
     * @return Model
     *
     * Method này cố ý tách update thành 2 phần:
     * - update bảng `users`;
     * - update bảng `business_users`.
     */
    public function update(int $id, array $data): Model
    {
        /**
         * Update user được tách rõ:
         * - trường tài khoản cấp hệ thống: name, email, phone...;
         * - trường membership cấp business: role, status, is_owner.
         *
         * Cách tách này giúp tránh rối loại khi sau này một user có nhiều business.
         */
        return DB::transaction(function () use ($id, $data) {
            $businessId = $this->resolveBusinessId($data);
            $user = $this->userRepository->findForBusiness($businessId, $id);

            if (! $user) {
                abort(404);
            }

            $userData = $this->extractUserAttributes($data, true);
			
            if (array_key_exists('password', $userData)) {
                $userData['password'] = Hash::make($userData['password']);
            }

            if ($userData !== []) {
                $this->userRepository->updateRecord($user, $userData);
            }

            // Thông tin user và membership là hai lớp dữ liệu khác nhau nên cần update tách biệt.
            $membershipData = Arr::only($data, ['role', 'membership_status', 'is_owner']);
            $normalizedMembership = [];
			
            if (array_key_exists('role', $membershipData)) {
                $normalizedMembership['role'] = $membershipData['role'];
            }

            if (array_key_exists('membership_status', $membershipData)) {
                $normalizedMembership['status'] = $membershipData['membership_status'];
            }

            if (array_key_exists('is_owner', $membershipData)) {
                $normalizedMembership['is_owner'] = (bool) $membershipData['is_owner'];
            }

            if ($normalizedMembership !== []) {
                $membership = $this->userRepository->findMembershipForBusiness($user, $businessId);
                $this->userRepository->updateMembership($membership, $normalizedMembership);
            }

            return $this->loadUserRelations($user, $businessId);
        });
    }

    /**
     * Xóa user khỏi business hiện tại.
     *
     * @param  int  $id
     * @param  array<string, mixed>  $data
     * @return Model
     *
     * Đây không nhất thiết là xóa tài khoản hệ thống:
     * - nếu user còn membership khác thì chỉ xóa membership hiện tại;
     * - nếu hết membership thì mới xóa record user.
     */
    public function delete(int $id, array $data): Model
    {
        /**
         * Xóa user trong MVP là xóa membership trước.
         *
         * Nếu user vẫn còn thuộc business khác thì chỉ bỏ membership hiện tại.
         * Chỉ khi không còn membership nào mới xóa tài khoản hệ thống.
         */
        return DB::transaction(function () use ($id, $data) {
            $businessId = $this->resolveBusinessId($data);
            $user = $this->userRepository->findForBusiness($businessId, $id);

            if (! $user) {
                abort(404);
            }

            // Xóa membership trong business hiện tại trước; chỉ xóa user hệ thống nếu không còn membership nào.
            $this->userRepository->deleteMembershipForBusiness($user, $businessId);

            if (! $this->userRepository->hasMemberships($user)) {
                $this->userRepository->deleteRecord($user);
            }

            return $user;
        });
    }

    /**
     * Resolve `business_id` dựa trên payload và business context hiện tại.
     *
     * @param  array<string, mixed>  $data
     * @return int
     */
    protected function resolveBusinessId(array $data = []): int
    {
        return $this->businessContext->resolveBusinessId(isset($data['business_id']) ? (int) $data['business_id'] : null);
    }

    /**
     * Load lại user kèm membership của business hiện tại.
     *
     * @param  Model  $user
     * @param  int  $businessId
     * @return Model
     *
     * Chỉ load membership của business hiện tại để response gọn hơn.
     */
    protected function loadUserRelations(Model $user, int $businessId): Model
    {
        /**
         * Response user chỉ cần membership của business hiện tại.
         *
         * Nếu eager load toàn bộ membership thì response sẽ dài,
         * trong khi frontend của MVP chưa cần nhìn user trong nhiều business cùng lúc.
         */
        // Khi trả response chỉ eager load membership của business đang thao tác để transformer cho ra dữ liệu gọn.
        return $user->fresh([
            'businessMemberships' => function ($membershipQuery) use ($businessId) {
                $membershipQuery->where('business_id', $businessId)->with('business');
            },
        ]) ?? $user;
    }

    /**
     * Tách các field hợp lệ để ghi vào bảng `users`.
     *
     * @param  array<string, mixed>  $data
     * @param  bool  $allowManagementFields  Co cho phep lay them phone/avatar/is_active hay không
     * @return array<string, mixed>
     *
     * Hàm này đóng vai trò whitelist để tránh ghi nhầm field ngoài ý muốn vào model User.
     */
    protected function extractUserAttributes(array $data, bool $allowManagementFields = false): array
    {
        // Chỉ lấy những field được phép ghi xuống bảng `users`.
        $fields = ['name', 'email', 'password'];

		// cái $allowManagementFields = true   thì cho phép thay đổi thêm phone . avatar và is_active
        if ($allowManagementFields) {
            $fields = array_merge($fields, ['phone', 'avatar', 'is_active']);
        }

        $userData = Arr::only($data, $fields);
		// kiểm tra xem key password có tồn tại hay không , và check giá trị rỗng hay không ,  => mục đích là  để check việc thay đổi mật khẩu
        if (array_key_exists('password', $userData) && blank($userData['password'])) {
            unset($userData['password']);
        }

        return $userData;
    }
}
