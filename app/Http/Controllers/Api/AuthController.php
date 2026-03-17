<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\RegisterUserRequest;
use App\Services\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Helpers\JwtHelper;
use Illuminate\Support\Facades\Cache;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly AuthService $authService)
    {
    }

    public function register(RegisterUserRequest $request)
    {
        return $this->successResponse(
            message: __('messages.register.action_created_success'),
            code: 'register',
            httpStatus: Controller::HTTP_OK,
            data: $this->authService->register($request->validated()),
        );
    }

    public function login(LoginUserRequest $request)
    {
        return $this->successResponse(
            message: __('messages.user.user_login_success'),
            code: 'login_success',
            httpStatus: Controller::HTTP_OK,
            data: $this->authService->login($request->validated())
        );
    }

    public function me(Request $request)
    {
        $user = app('jwt_user');
        return response()->json($user);
    }

    public function logout(Request $request)
    {
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
