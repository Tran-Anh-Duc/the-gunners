<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    // $module, $action passed via route middleware parameters
    public function handle(Request $request, Closure $next, $module, $action)
    {
        $permissionName = "{$module}_{$action}"; // e.g. edit_user_management

        $user = $request->user(); // hoặc auth()->user()

        if (!$user || !$user->hasPermission($permissionName)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        return $next($request);
    }
}
