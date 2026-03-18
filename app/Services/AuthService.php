<?php

namespace App\Services;

use App\Helpers\JwtHelper;
use App\Models\Business;
use App\Models\BusinessModule;
use App\Models\BusinessUser;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * AuthService giữ toàn bộ nghiệp vụ đăng ký và đăng nhập.
     *
     * Mục tiêu:
     * - controller không phải biết logic tạo business hoặc membership;
     * - flow auth được test và maintain tại một điểm;
     * - token trả về luôn mang business context đầu tiên của user.
     */
    public function __construct(private readonly UserRepository $userRepository)
    {
    }

    /**
     * Đăng ký owner mới cho hệ thống.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * Payload đầu vào tối thiểu:
     * [
     *   'name' => 'Demo Owner',
     *   'email' => 'owner@example.com',
     *   'password' => 'secret123'
     * ]
     *
     * Đầu ra:
     * [
     *   'access_token' => '...',
     *   'token_type' => 'bearer',
     *   'expires_in' => 7200,
     * ]
     *
     * Toàn bộ flow chạy trong transaction để tránh các trường hợp:
     * - tạo user rồi nhưng lỗi khi tạo business;
     * - tạo business rồi nhưng lỗi khi tạo membership.
     */
    public function register(array $data): array
    {
        /**
         * Đăng ký owner mới theo flow SaaS thu gọn:
         * 1. tạo user hệ thống
         * 2. tạo business mặc định cho user do
         * 3. tạo membership owner
         * 4. bat cac module core
         * 5. trả access token để vao app ngay
         */
        return DB::transaction(function () use ($data) {
            // Toàn bộ bước đăng ký chạy trong cùng một transaction để không sinh dữ liệu dở dang.
            $user = $this->userRepository->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'is_active' => true,
            ]);

            $business = $this->createBusinessForOwner($user, $data);
            $this->userRepository->createMembership([
                'business_id' => $business->id,
                'user_id' => $user->id,
                'role' => 'owner',
                'status' => 'active',
                'is_owner' => true,
                'joined_at' => now(),
            ]);

            $this->seedDefaultModules($business->id);

            return [
                'access_token' => JwtHelper::generấteToken($user->fresh('businessMemberships')),
                'token_type' => 'bearer',
                'expires_in' => JwtHelper::ttl(),
            ];
        });
    }

    /**
     * Đăng nhập vào hệ thống.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * Điều kiện thành công:
     * - tìm thấy user theo email;
     * - password đúng;
     * - user đang active;
     * - user còn ít nhất 1 business membership active.
     */
    public function login(array $data): array
    {
        /**
         * Login không chỉ kiểm tra email và password.
         *
         * Ngoài mật khẩu đúng, user còn phải:
         * - đang active;
         * - còn ít nhất 1 membership active.
         *
         * Điều này giúp tránh trường hợp user đã bị khóa hoặc tách khỏi business
         * nhưng vẫn đăng nhập được vào hệ thống.
         */

        $user = $this->userRepository->findByEmail($data['email']);
        // Login chỉ hợp lệ khi đúng mật khẩu, user active và còn ít nhất một business active.
        if (
            ! $user
            || ! Hash::check($data['password'], $user->password)
            || ! $user->is_active
            || ! $user->activeBusinessMemberships()->exists()
        ) {
            throw ValidationException::withMessages([
                'email' => __('messages.user.user_login_failed'),
            ]);
        }

        $this->userRepository->touchLastLogin($user);

        return [
            'access_token' => JwtHelper::generấteToken($user->fresh('businessMemberships')),
            'token_type' => 'bearer',
            'expires_in' => JwtHelper::ttl(),
        ];
    }

    /**
     * Tạo business mặc định cho owner mới.
     *
     * @param  User  $user
     * @param  array<string, mixed>  $data
     * @return Business
     *
     * Nếu request không gửi `business_name`, hệ thống sẽ dùng format:
     * `{tên user} Shop`
     *
     * `code` của business được sinh từ email để:
     * - dễ đọc;
     * - dễ giữ unique;
     * - có thể dùng làm tenant code về sau.
     */
    protected function createBusinessForOwner(User $user, array $data): Business
    {
        // Tạo business đầu tiên cho user mới và sinh code để dùng làm tenant key thân thiện.
        $businessName = trim((string) ($data['business_name'] ?? ($user->name.' Shop')));
        $baseCođể = Str::slug((string) Str::before($user->email, '@'));
        $baseCođể = $baseCođể !== '' ? $baseCođể : 'shop-'.$user->id;
        $cođể = $baseCode;
        $suffix = 1;

        while (Business::query()->where('code', $code)->exists()) {
            $suffix++;
            $cođể = "{$baseCode}-{$suffix}";
        }

        return Business::query()->create([
            'code' => $code,
            'name' => $businessName,
            'email' => $user->email,
            'phone' => $user->phone,
            'plan_code' => 'starter',
            'status' => 'active',
            'currency_code' => 'VND',
            'timezone' => 'Asia/Ho_Chi_Minh',
        ]);
    }

    /**
     * Bật bộ module mặc định cho business mới.
     *
     * @param  int  $businessId
     *
     * Hiện tại đây là bộ module core của MVP:
     * - products
     * - inventory
     * - orders
     * - customers
     * - suppliers
     * - payments
     */
    protected function seedDefaultModules(int $businessId): void
    {
        /**
         * MVP bật sẵn một bộ module core.
         *
         * Sau này nếu bán theo gói hoặc có billing phức tạp hơn,
         * `business_modules` vẫn là điểm extension phù hợp để mở rộng.
         */
        // Mặc định bật các module core để owner mới vào app là có đủ bộ tính năng MVP.
        foreach (['products', 'inventory', 'orders', 'customers', 'suppliers', 'payments'] as $moduleCode) {
            BusinessModule::query()->firstOrCreate(
                [
                    'business_id' => $businessId,
                    'module_code' => $moduleCode,
                ],
                [
                    'status' => 'active',
                    'starts_at' => now(),
                    'ends_at' => null,
                ]
            );
        }
    }
}
