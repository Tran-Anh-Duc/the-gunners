<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use App\Helpers\JwtHelper;
use Firebase\JWT\ExpiredException;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class JwtMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['error' => 'Token not provided'], 401);
        }

        // check blacklist
        if (Cache::has('jwt_blacklist_' . sha1($token))) {
            return response()->json(['error' => 'Token is blacklisted'], 401);
        }

        try {
            $payload = JwtHelper::decodeToken($token);
            $userId = $payload->sub ?? null;
            if (!$userId) {
                return response()->json(['error' => 'Invalid token payload'], 401);
            }

            $user = User::find($userId);
            if (!$user) {
                return response()->json(['error' => 'User not found'], 401);
            }

            // attach user to request and auth
            $request->attributes->set('jwt_payload', $payload);
            app()->instance('jwt_user', $user);

            // optional: set auth()->setUser($user) if you want Laravel auth() to work
            auth()->setUser($user);

        } catch (ExpiredException $e) {
            return response()->json(['error' => 'Token expired'], 401);
        } catch (Exception $e) {
            return response()->json(['error' => 'Invalid token: '.$e->getMessage()], 401);
        }

        return $next($request);
    }
}
