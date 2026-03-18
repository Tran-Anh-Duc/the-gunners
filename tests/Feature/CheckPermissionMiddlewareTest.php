<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckPermission;
use Illuminate\Http\Request;
use Tests\TestCase;

class CheckPermissionMiddlewareTest extends TestCase
{
    /**
     * Bảo đảm middleware hiểu đúng format permission mới dạng `module.action`.
     */
    public function test_it_accepts_the_current_dot_permission_contract(): void
    {
        // Middleware phải chấp nhận contract permission mới dạng `module.action`.
        $request = Request::create('/api/users', 'POST');
        $request->setUserResolver(fn () => new class
        {
            public function hasPermission(string $permission): bool
            {
                return $permission === 'users.create';
            }
        });

        $response = (new CheckPermission())->handle(
            $request,
            fn () => response()->json(['ok' => true]),
            'users',
            'create'
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    /**
     * Giữ tương thích ngược với format permission cũ dạng `module_action`.
     */
    public function test_it_keeps_legacy_underscore_permissions_working(): void
    {
        // Vẫn phải hỗ trợ format cũ để không làm vỡ dữ liệu permission hiện hữu.
        $request = Request::create('/api/users', 'POST');
        $request->setUserResolver(fn () => new class
        {
            public function hasPermission(string $permission): bool
            {
                return $permission === 'users_add';
            }
        });

        $response = (new CheckPermission())->handle(
            $request,
            fn () => response()->json(['ok' => true]),
            'users',
            'add'
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    /**
     * Bảo đảm request bị chặn khi không có permission phù hợp.
     */
    public function test_it_rejects_requests_without_matching_permission(): void
    {
        // Không có permission phù hợp thì middleware phải trả về 403.
        $request = Request::create('/api/users', 'POST');
        $request->setUserResolver(fn () => new class
        {
            public function hasPermission(string $permission): bool
            {
                return false;
            }
        });

        $response = (new CheckPermission())->handle(
            $request,
            fn () => response()->json(['ok' => true]),
            'users',
            'create'
        );

        $this->assertSame(403, $response->getStatusCode());
    }
}
