<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckPermission;
use Illuminate\Http\Request;
use Tests\TestCase;

class CheckPermissionMiddlewareTest extends TestCase
{
    public function test_it_accepts_the_current_dot_permission_contract(): void
    {
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

    public function test_it_keeps_legacy_underscore_permissions_working(): void
    {
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

    public function test_it_rejects_requests_without_matching_permission(): void
    {
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
