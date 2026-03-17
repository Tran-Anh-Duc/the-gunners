<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $module, string $action): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        foreach ($this->candidatePermissionNames($module, $action) as $permissionName) {
            if ($user->hasPermission($permissionName)) {
                return $next($request);
            }
        }

        return response()->json(['error' => 'Forbidden'], 403);
    }

    protected function candidatePermissionNames(string $module, string $action): array
    {
        $normalizedAction = match ($action) {
            'add' => 'create',
            'edit' => 'update',
            default => $action,
        };

        return array_values(array_unique([
            "{$module}.{$normalizedAction}",
            "{$module}_{$action}",
        ]));
    }
}
