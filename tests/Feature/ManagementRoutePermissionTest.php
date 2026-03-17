<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Tests\TestCase;

class ManagementRoutePermissionTest extends TestCase
{
    public function test_user_management_routes_are_protected_by_user_permissions(): void
    {
        $indexRoute = app('router')->getRoutes()->match(Request::create('/api/users', 'GET'));
        $storeRoute = app('router')->getRoutes()->match(Request::create('/api/auth/users', 'POST'));
        $updateRoute = app('router')->getRoutes()->match(Request::create('/api/auth/users/1', 'PUT'));
        $deleteRoute = app('router')->getRoutes()->match(Request::create('/api/auth/users/1', 'DELETE'));

        $this->assertContains('permission:users,view', $indexRoute->gatherMiddleware());
        $this->assertContains('permission:users,create', $storeRoute->gatherMiddleware());
        $this->assertContains('permission:users,update', $updateRoute->gatherMiddleware());
        $this->assertContains('permission:users,delete', $deleteRoute->gatherMiddleware());
    }

    public function test_product_routes_are_protected_by_product_permissions(): void
    {
        $indexRoute = app('router')->getRoutes()->match(Request::create('/api/products', 'GET'));
        $storeRoute = app('router')->getRoutes()->match(Request::create('/api/products', 'POST'));
        $updateRoute = app('router')->getRoutes()->match(Request::create('/api/products/1', 'PUT'));
        $deleteRoute = app('router')->getRoutes()->match(Request::create('/api/products/1', 'DELETE'));

        $this->assertContains('permission:products,view', $indexRoute->gatherMiddleware());
        $this->assertContains('permission:products,create', $storeRoute->gatherMiddleware());
        $this->assertContains('permission:products,update', $updateRoute->gatherMiddleware());
        $this->assertContains('permission:products,delete', $deleteRoute->gatherMiddleware());
    }

    public function test_inventory_routes_are_protected_by_inventory_permissions(): void
    {
        $stockInStoreRoute = app('router')->getRoutes()->match(Request::create('/api/stock-in', 'POST'));
        $stockOutStoreRoute = app('router')->getRoutes()->match(Request::create('/api/stock-out', 'POST'));
        $inventoryStocksRoute = app('router')->getRoutes()->match(Request::create('/api/inventory/stocks', 'GET'));
        $stockAdjustmentStoreRoute = app('router')->getRoutes()->match(Request::create('/api/stock-adjustments', 'POST'));
        $stockInConfirmRoute = app('router')->getRoutes()->match(Request::create('/api/stock-in/1/confirm', 'POST'));

        $this->assertContains('permission:inventory,create', $stockInStoreRoute->gatherMiddleware());
        $this->assertContains('permission:inventory,create', $stockOutStoreRoute->gatherMiddleware());
        $this->assertContains('permission:inventory,create', $stockAdjustmentStoreRoute->gatherMiddleware());
        $this->assertContains('permission:inventory,update', $stockInConfirmRoute->gatherMiddleware());
        $this->assertContains('permission:inventory,view', $inventoryStocksRoute->gatherMiddleware());
    }

    public function test_order_and_payment_routes_are_protected_by_their_permissions(): void
    {
        $orderStoreRoute = app('router')->getRoutes()->match(Request::create('/api/orders', 'POST'));
        $orderConfirmRoute = app('router')->getRoutes()->match(Request::create('/api/orders/1/confirm', 'POST'));
        $paymentStoreRoute = app('router')->getRoutes()->match(Request::create('/api/payments', 'POST'));
        $paymentIndexRoute = app('router')->getRoutes()->match(Request::create('/api/payments', 'GET'));
        $paymentCancelRoute = app('router')->getRoutes()->match(Request::create('/api/payments/1/cancel', 'POST'));

        $this->assertContains('permission:orders,create', $orderStoreRoute->gatherMiddleware());
        $this->assertContains('permission:orders,update', $orderConfirmRoute->gatherMiddleware());
        $this->assertContains('permission:payments,create', $paymentStoreRoute->gatherMiddleware());
        $this->assertContains('permission:payments,view', $paymentIndexRoute->gatherMiddleware());
        $this->assertContains('permission:payments,update', $paymentCancelRoute->gatherMiddleware());
    }
}
