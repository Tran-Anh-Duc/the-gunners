<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\RegisterUserRequest;
use App\Services\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Helpers\JwtHelper;
use Illuminate\Support\Facades\Cache;

/**
 * Controller xác thực của API.
 *
 * Vai trò của controller này:
 * - nhận request đăng ký hoặc đăng nhập;
 * - chuyển nghiệp vụ cho `AuthService`;
 * - trả token hoặc thông tin user về cho client.
 *
 * Riêng `me()` và `logout()` chỉ thao tác với user hoặc token
 * đã có sẵn trong request context.
 */
class AuthController extends ApiController
{
    use ApiResponse;
    public function __construct(private readonly AuthService $authService)
    {
    }

    /**
     * Đăng ký tài khoản owner mới.
     *
     * Thành phần đầu vào:
     * - `RegisterUserRequest` validate `name`, `email`, `password`
     *
     * Cách xử lý:
     * - AuthService::register()
     * - service sẽ tạo user, business mặc định, membership owner và token
     *
     * Kết quả trả ra:
     * - access token
     * - token_type
     * - expires_in
     */
    public function register(RegisterUserRequest $request)
    {
        // Controller chỉ nhận request hợp lệ và trả response; nghiệp vụ nằm trong AuthService.
        return $this->successResponse(
            message: __('messages.register.action_created_success'),
            code: 'register',
            httpStatus: self::HTTP_OK,
            data: $this->authService->register($request->validated()),
        );
    }

    /**
     * Đăng nhập và nhận JWT token.
     *
     * Thành phần đầu vào:
     * - `LoginUserRequest` validate `email`, `password`
     *
     * Cách xử lý:
     * - AuthService::login()
     * - service sẽ kiểm tra tài khoản, membership active và phát hành token
     *
     * Kết quả trả ra:
     * - token để client lưu lại và gọi các API tiếp theo
     */
    public function login(LoginUserRequest $request)
    {
        // Login flow được đưa hết vào service để controller giữ đúng vai trò delivery layer.
        return $this->successResponse(
            message: __('messages.user.user_login_success'),
            code: 'login_success',
            httpStatus: self::HTTP_OK,
            data: $this->authService->login($request->validated())
        );
    }

    /**
     * Trả user hiện tại đã được middleware JWT xác thực.
     *
     * Endpoint này không cần service vì chỉ đọc dữ liệu đã được middleware JWT
     * bind sẵn vào request hoặc container.
     */
    public function me(Request $request)
    {
        // Trả user đã được middleware JWT xác thực và bind vào app container.
        $user = app('jwt_user');
        return response()->json($user);
    }

    /**
     * Đăng xuất theo cơ chế blacklist token.
     *
     * Thành phần đầu vào:
     * - bearer token trên `Authorization` header
     *
     * Logic:
     * - decode token
     * - lấy thời gian hết hạn
     * - đưa token vào cache blacklist đến khi hết hạn
     *
     * Kết quả:
     * - thông báo logout thành công hoặc lỗi token
     */
    public function logout(Request $request)
    {
        /**
         * Logout theo cơ chế blacklist token.
         *
         * Vì JWT là stateless, không có session phía server để "hủy" trực tiếp.
         * Cách đơn giản cho MVP là đưa token vào cache blacklist đến khi hết hạn.
         */
        $token = $request->bearerToken();
        if (!$token) {
            return response()->json(['error'=>'Token not provided'], 401);
        }

        try {
            $payload = JwtHelper::decodeToken($token);
            $exp = JwtHelper::getTokenExpiryFromPayload($payload);
            $ttl = $exp ? ($exp - time()) : JwtHelper::ttl();

            $key = 'jwt_blacklist_' . sha1($token);
            Cache::put($key, true, $ttl);

            return response()->json(['message'=>'Logged out']);
        } catch (\Exception $e) {
            return response()->json(['error'=>'Invalid token'], 401);
        }
    }
}
