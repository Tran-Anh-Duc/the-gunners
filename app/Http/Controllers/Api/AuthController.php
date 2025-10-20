<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Helpers\JwtHelper;
use Illuminate\Support\Facades\Cache;
use function Symfony\Component\HttpKernel\preBoot;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name'=>'required|string|max:255',
            'email'=>'required|email|unique:users',
            'password'=>'required|string|min:6'
        ]);

        $user = User::create([
            'name'=>$request->name,
            'email'=>$request->email,
            'password'=>Hash::make($request->password),
        ]);
        $token = JwtHelper::generateToken($user);
        return response()->json([
            'access_token'=>$token,
            'token_type'=>'bearer',
            'expires_in'=>JwtHelper::ttl()
        ]);
    }

    public function login(Request $request)
    {

        $request->validate(['email'=>'required|email','password'=>'required']);
        $user = User::where('email', $request->email)->first();


        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['error'=>'Unauthorized'], 401);
        }

        $user->update(['last_login_at' => now()]);
        $token = JwtHelper::generateToken($user);

        return response()->json([
            'access_token'=>$token,
            'token_type'=>'bearer',
            'expires_in'=>JwtHelper::ttl()
        ]);
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
