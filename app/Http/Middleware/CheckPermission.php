<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware kiểm tra quyền truy cập theo module và hành động.
 *
 * Lớp này đứng sau xác thực:
 * - nếu chưa có user, request bị chặn ngay;
 * - nếu có user, middleware thử nhiều cách ghép tên permission để giữ tương thích
 *   giữa format mới (`products.view`) và format cũ (`products_view`).
 */
class CheckPermission
{
    public function handle(Request $request, Closure $next, string $module, string $action): Response
    {
        // User đã được xác thực ở lớp middleware JWT/Auth trước khi vào đây.
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        foreach ($this->candidatePermissionNames($module, $action) as $permissionName) {
            // Chỉ cần khớp một tên quyền là cho phép request đi tiếp.
            if ($user->hasPermission($permissionName)) {
                return $next($request);
            }
        }

        return response()->json(['error' => 'Forbidden'], 403);
    }

    protected function candidatePermissionNames(string $module, string $action): array
    {
        // Chuẩn hóa một vài action cũ để tránh lệch tên quyền giữa route và dữ liệu DB.
        $normalizedAction = match ($action) {
            'add' => 'create',
            'edit' => 'update',
            default => $action,
        };

        // Trả về danh sách unique để middleware lần lượt thử theo thứ tự ưu tiên.
        return array_values(array_unique([
            "{$module}.{$normalizedAction}",
            "{$module}_{$action}",
        ]));
    }
}
