<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Validation\ValidationException;

class BusinessContext
{
    /**
     * Resolve người dùng hiện tại từ request context.
     *
     * @return User|null
     *
     * Thứ tự ưu tiên:
     * 1. `jwt_user` do `JwtMiddleware` bind vào container;
     * 2. `auth()->user()` cho các trường hợp test hoặc script nội bộ.
     */
    public function currentUser(): ?User
    {
        // Ưu tiên user lấy từ JWT vì đây là nguồn đã đi qua xác thực token của API.
        if (app()->bound('jwt_user')) {
            /** @var User $user */
            $user = app('jwt_user');

            return $user;
        }

        return auth()->user();
    }

    /**
     * Resolve `business_id` dùng cho toàn bộ service layer.
     *
     * @param  int|null  $requestedBusinessId  `business_id` client gửi lên nếu có
     * @return int
     *
     * Luồng xử lý:
     * 1. Nếu đã đăng nhập và có gửi `business_id`, kiểm tra user có membership đang hoạt động hay không.
     * 2. Nếu đã đăng nhập nhưng không gửi `business_id`, lấy business đang hoạt động đầu tiên.
     * 3. Nếu không có user nhưng có `requestedBusinessId`, cho phép dùng trong test hoặc script nội bộ.
     * 4. Nếu vẫn không xác định được, ném `ValidationException`.
     */
    public function resolveBusinessId(?int $requestedBusinessId = null): int
    {
        /**
         * Đây là điểm trung tâm của cơ chế multi-tenant.
         *
         * Mọi request business-scoped đều nên đi qua đây để:
         * - xác nhận user hiện tại thực sự thuộc business được yêu cầu;
         * - hoặc tự suy ra business mặc định khi client không truyền lên.
         *
         * Cách gom logic này giúp controller, request và service không phải
         * tự lặp lại cùng một đoạn kiểm tra tenant ở nhiều nơi.
         */
        $user = $this->currentUser();

        if ($user) {
            // Nếu client gửi rõ business_id thì bắt buộc user phải có quyền trong business đó.
            if ($requestedBusinessId !== null) {
                $hasAccess = $user->activeBusinessMemberships()
                    ->where('business_id', $requestedBusinessId)
                    ->exists();

                if (! $hasAccess) {
                    throw ValidationException::withMessages([
                        'business_id' => 'Business not found or inaccessible.',
                    ]);
                }

                return $requestedBusinessId;
            }

            // Không gửi business_id thì tự rơi về business active đầu tiên của user.
            $defaultBusinessId = $user->activeBusinessMemberships()->value('business_id');

            if ($defaultBusinessId !== null) {
                return (int) $defaultBusinessId;
            }
        }

        if ($requestedBusinessId !== null) {
            // Cho phép test hoặc script nội bộ đẩy business_id trực tiếp mà không cần user đăng nhập.
            return $requestedBusinessId;
        }

        throw ValidationException::withMessages([
            'business_id' => 'Business context is required.',
        ]);
    }
}
