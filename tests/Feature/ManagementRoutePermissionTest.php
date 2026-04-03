<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Tests\TestCase;

class ManagementRoutePermissionTest extends TestCase
{
    /**
     * Khóa contract phân quyền cho các route quản lý user.
     *
     * Nếu middleware bị gỡ hoặc gắn sai action, test này sẽ báo đỏ ngay.
     */
    public function test_user_management_routes_are_protected_by_user_permissions(): void
    {
        // Các route nhạy cảm phải gắn đúng middleware permission theo action.
        $indexRoute = app('router')->getRoutes()->match(Request::create('/api/users', 'GET'));
        $storeRoute = app('router')->getRoutes()->match(Request::create('/api/users', 'POST'));
        $updateRoute = app('router')->getRoutes()->match(Request::create('/api/users/1', 'PUT'));
        $deleteRoute = app('router')->getRoutes()->match(Request::create('/api/users/1', 'DELETE'));

        $this->assertContains('permission:users,view', $indexRoute->gatherMiddleware());
        $this->assertContains('permission:users,create', $storeRoute->gatherMiddleware());
        $this->assertContains('permission:users,update', $updateRoute->gatherMiddleware());
        $this->assertContains('permission:users,delete', $deleteRoute->gatherMiddleware());
    }

    /**
     * Khóa contract phân quyền cho module sản phẩm.
     */
    public function test_product_routes_are_protected_by_product_permissions(): void
    {
        // `products` tách riêng khỏi `inventory` để có thể phân quyền hoặc bán module độc lập.
        $indexRoute = app('router')->getRoutes()->match(Request::create('/api/products', 'GET'));
        $storeRoute = app('router')->getRoutes()->match(Request::create('/api/products', 'POST'));
        $updateRoute = app('router')->getRoutes()->match(Request::create('/api/products/1', 'PUT'));
        $deleteRoute = app('router')->getRoutes()->match(Request::create('/api/products/1', 'DELETE'));
        $categoryStoreRoute = app('router')->getRoutes()->match(Request::create('/api/categories', 'POST'));
        $categoryDeleteRoute = app('router')->getRoutes()->match(Request::create('/api/categories/1', 'DELETE'));

        $this->assertContains('permission:products,view', $indexRoute->gatherMiddleware());
        $this->assertContains('permission:products,create', $storeRoute->gatherMiddleware());
        $this->assertContains('permission:products,update', $updateRoute->gatherMiddleware());
        $this->assertContains('permission:products,delete', $deleteRoute->gatherMiddleware());
        $this->assertContains('permission:products,create', $categoryStoreRoute->gatherMiddleware());
        $this->assertContains('permission:products,delete', $categoryDeleteRoute->gatherMiddleware());
    }

    /**
     * Khóa contract phân quyền cho module inventory.
     */
    public function test_inventory_routes_are_protected_by_inventory_permissions(): void
    {
        // `inventory` gom nhập, xuất, kiểm kho và xem tồn kho.
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

    /**
     * Khóa contract phân quyền cho module order và payment.
     */
    public function test_order_and_payment_routes_are_protected_by_their_permissions(): void
    {
        // `orders` và `payments` là hai module tách quyền riêng để dễ mở rộng theo gói sau này.
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
