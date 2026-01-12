<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\StoreUserRequest;
use App\Repositories\UserRepository;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Helpers\JwtHelper;
use Illuminate\Support\Facades\Cache;
use function Symfony\Component\HttpKernel\preBoot;

class AuthController extends Controller
{
    use ApiResponse;
    protected UserRepository $userRepository;
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function register(StoreUserRequest $request)
    {
        $data = $request->all();
        $resultData = $this->userRepository->registerAuth($data);
        return $this->successResponse(
            __('messages.register.action_created_success'),
            __('messages.register.action_created_success'),
            Controller::HTTP_OK,
            $resultData
        );
    }

    public function login(LoginUserRequest $request)
    {
        $data = $request->all();
        $user = $this->userRepository->loginUser($data);
        return $this->successResponse(
            message: __('messages.user.user_login_success'),
            code: 'login_success',
            httpStatus: Controller::HTTP_OK,
            data: $user
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
