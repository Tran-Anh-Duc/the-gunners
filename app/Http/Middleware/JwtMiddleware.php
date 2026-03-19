<?php

namespace App\Http\Middleware;

use App\Helpers\JwtHelper;
use App\Models\User;
use Closure;
use Exception;
use Firebase\JWT\ExpiredException;
use Illuminate\Http\Request;
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
            $businessId = isset($payload->data->business_id) ? (int) $payload->data->business_id : null;

            if (!$userId) {
                return response()->json(['error' => 'Invalid token payload'], 401);
            }

            // Nạp luôn active membership hiện tại để các lớp dưới tái sử dụng, tránh query lặp lại.
            $user = User::query()
                ->with(['activeBusinessMemberships' => function ($query) use ($businessId) {
                    if ($businessId !== null) {
                        $query->where('business_id', $businessId);
                    }

                    $query->orderByDesc('is_owner')->orderBy('id');
                }])
                ->find($userId);

            if (!$user) {
                return response()->json(['error' => 'User not found'], 401);
            }

            $activeMembership = $user->activeBusinessMemberships->first();
            if (!$activeMembership) {
                return response()->json(['error' => 'User membership not found'], 401);
            }

            $businessName = $payload->data->business_name ?? null;

            // attach user to request and auth
            $request->attributes->set('jwt_payload', $payload);
            $request->attributes->set('jwt_user', $user);
            $request->attributes->set('jwt_active_membership', $activeMembership);
            $request->attributes->set('jwt_business_id', (int) $activeMembership->business_id);
            $request->attributes->set('jwt_business_name', $businessName);

            app()->instance('jwt_user', $user);
            app()->instance('jwt_active_membership', $activeMembership);
            app()->instance('jwt_business_id', (int) $activeMembership->business_id);
            app()->instance('jwt_business_name', $businessName);

            // optional: set auth()->setUser($user) if you want Laravel auth() to work
            auth()->setUser($user);
        } catch (ExpiredException $e) {
            return response()->json(['error' => 'Token expired'], 401);
        } catch (Exception $e) {
            return response()->json(['error' => 'Invalid token: ' . $e->getMessage()], 401);
        }

        return $next($request);
    }
}
