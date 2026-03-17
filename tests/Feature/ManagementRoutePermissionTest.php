<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Tests\TestCase;

class ManagementRoutePermissionTest extends TestCase
{
    public function test_role_routes_are_protected_by_role_permissions(): void
    {
        $indexRoute = app('router')->getRoutes()->match(Request::create('/api/role', 'GET'));
        $storeRoute = app('router')->getRoutes()->match(Request::create('/api/role', 'POST'));
        $updateRoute = app('router')->getRoutes()->match(Request::create('/api/role/1', 'PUT'));

        $this->assertContains('permission:roles,view', $indexRoute->gatherMiddleware());
        $this->assertContains('permission:roles,create', $storeRoute->gatherMiddleware());
        $this->assertContains('permission:roles,update', $updateRoute->gatherMiddleware());
    }

    public function test_department_routes_are_protected_by_department_permissions(): void
    {
        $indexRoute = app('router')->getRoutes()->match(Request::create('/api/department', 'GET'));
        $storeRoute = app('router')->getRoutes()->match(Request::create('/api/department', 'POST'));
        $updateRoute = app('router')->getRoutes()->match(Request::create('/api/department/1', 'PUT'));

        $this->assertContains('permission:departments,view', $indexRoute->gatherMiddleware());
        $this->assertContains('permission:departments,create', $storeRoute->gatherMiddleware());
        $this->assertContains('permission:departments,update', $updateRoute->gatherMiddleware());
    }
}
