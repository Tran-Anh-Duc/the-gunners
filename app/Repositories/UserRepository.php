<?php

namespace App\Repositories;

use App\Models\BusinessUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class UserRepository extends BaseRepository
{
    public function __construct(User $user)
    {
        $this->model = $user;
    }

    public function getModel()
    {
        return User::class;
    }

    /**
     * Tìm user theo email cho flow login.
     *
     * @param  string  $email
     * @return User|null
     */
    public function findByEmail(string $email): ?User
    {
        // Flow đăng nhập cần lookup user toàn hệ thống, chưa scope theo business ở bước này.
        return User::query()->where('email', $email)->first();
    }

    /**
     * Cập nhật thời điểm đăng nhập cuối.
     *
     * @param  User  $user
     */
    public function touchLastLogin(User $user): void
    {
        // Tách helper này để mọi flow login dùng cùng một cách ghi nhận lần đăng nhập cuối.
        $user->update(['last_login_at' => now()]);
    }

    /**
     * Tạo query user trong business thông qua bảng membership.
     *
     * @param  int  $businessId
     * @param  array<string, mixed>  $filters
     * @return Builder
     *
     * Filter hiện tại hỗ trợ:
     * - name, email, phone
     * - role, membership_status
     */
    public function queryForBusiness(int $businessId, array $filters = []): Builder
    {
        /**
         * Query user theo business thông qua membership.
         *
         * Đây là điểm quan trọng để tránh lộ user của business A sang business B,
         * vì bảng `users` không chứa `business_id` trực tiếp.
         */
        // Query user trong phạm vi một business, đồng thời cho phép filter theo membership.
        $query = User::query()
            ->join('business_users as current_membership', function ($join) use ($businessId, $filters) {
                $join->on('users.id', '=', 'current_membership.user_id')
                    ->where('current_membership.business_id', '=', $businessId);

                if (! empty($filters['role'])) {
                    $join->where('current_membership.role', '=', $filters['role']);
                }

                if (! empty($filters['membership_status'])) {
                    $join->where('current_membership.status', '=', $filters['membership_status']);
                }
            })
            ->select([
                'users.*',
                'current_membership.business_id as membership_business_id',
                'current_membership.role as membership_role',
                'current_membership.status as membership_status',
                'current_membership.is_owner as membership_is_owner',
                'current_membership.joined_at as membership_joined_at',
            ]);

        if (! empty($filters['name'])) {
            $query->where('name', 'like', '%'.$filters['name'].'%');
        }

        if (! empty($filters['email'])) {
            $query->where('email', 'like', '%'.$filters['email'].'%');
        }

        if (! empty($filters['phone'])) {
            $query->where('phone', 'like', '%'.$filters['phone'].'%');
        }
        return $query->orderByDesc('users.id');
    }

    /**
     * Tìm user theo business scope.
     *
     * @param  int  $businessId
     * @param  int  $id
     * @return User|null
     */
    public function findForBusiness(int $businessId, int $id): ?User
    {
        // Dùng cho các case muốn tự xử lý not found ở tầng service/controller.
        // Hàm này chỉ trả user nếu user thực sự thuộc business hiện tại.
        return User::query()
            ->whereKey($id)
            ->whereHas('businessMemberships', function (Builder $membershipQuery) use ($businessId) {
                $membershipQuery->where('business_id', $businessId);
            })
            ->first();
    }

    /**
     * Tìm user theo business scope và fail ngay nếu không tồn tại.
     *
     * @param  int  $businessId
     * @param  int  $id
     * @return User
     */
    public function findForBusinessOrFail(int $businessId, int $id): User
    {
        // Dùng khi muốn để Eloquent ném `ModelNotFound` cho tầng trên xử lý.
        return User::query()
            ->whereKey($id)
            ->whereHas('businessMemberships', function (Builder $membershipQuery) use ($businessId) {
                $membershipQuery->where('business_id', $businessId);
            })
            ->firstOrFail();
    }

    /**
     * Update user record.
     *
     * @param  User  $user
     * @param  array<string, mixed>  $attributes
     * @return User
     */
    public function updateRecord(User $user, array $attributes): User
    {
        // Tách helper update để service gọi thống nhất và dễ mở rộng về sau.
        $user->update($attributes);

        return $user;
    }

    /**
     * Tạo membership mới trong business.
     *
     * @param  array<string, mixed>  $attributes
     * @return BusinessUser
     */
    public function createMembership(array $attributes): BusinessUser
    {
        /**
         * Membership là "user trong business" chứ không phải user hệ thống.
         *
         * Nhiều quy tắc nghiệp vụ như role, owner, status sẽ sống ở bảng này
         * thay vì đẩy vào bảng `users`.
         */
        // Membership là lớp gắn user với business, role và trạng thái sử dụng app.
        return BusinessUser::query()->create($attributes);
    }

    /**
     * Lấy membership của một user trong một business cụ thể.
     *
     * @param  User  $user
     * @param  int  $businessId
     * @return BusinessUser
     */
    public function findMembershipForBusiness(User $user, int $businessId): BusinessUser
    {
        // Đây là bản ghi mang thông tin role, status và ownership trong business hiện tại.
        return $user->businessMemberships()
            ->where('business_id', $businessId)
            ->firstOrFail();
    }

    /**
     * Update membership.
     *
     * @param  BusinessUser  $membership
     * @param  array<string, mixed>  $attributes
     * @return BusinessUser
     */
    public function updateMembership(BusinessUser $membership, array $attributes): BusinessUser
    {
        // Tách helper để tầng service không phải cập nhật trực tiếp model membership.
        $membership->update($attributes);

        return $membership;
    }

    /**
     * Xóa membership của user trong business hiện tại.
     *
     * @param  User  $user
     * @param  int  $businessId
     */
    public function deleteMembershipForBusiness(User $user, int $businessId): void
    {
        // Chỉ xóa membership của business hiện tại, không chạm vào business khác.
        $user->businessMemberships()->where('business_id', $businessId)->delete();
    }

    /**
     * Kiểm tra user còn thuộc business nào khác không.
     *
     * @param  User  $user
     * @return bool
     */
    public function hasMemberships(User $user): bool
    {
        // Nếu còn membership, user vẫn phải được giữ lại ở tầng hệ thống.
        return $user->businessMemberships()->exists();
    }

    /**
     * Xóa record user.
     *
     * @param  User  $user
     */
    public function deleteRecord(User $user): void
    {
        // Chỉ được gọi sau khi đã chắc chắn user không còn membership nào khác.
        $user->delete();
    }
}
