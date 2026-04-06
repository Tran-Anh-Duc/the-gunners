<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Category;
use App\Models\CurrentStock;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\InteractsWithBusinessApi;
use Tests\TestCase;

class NameSlugSearchApiTest extends TestCase
{
    use InteractsWithBusinessApi;
    use RefreshDatabase;

    protected Business $business;

    protected User $owner;

    protected Unit $unit;

    protected Warehouse $warehouse;

    protected Category $category;

    protected Customer $customer;

    protected Supplier $supplier;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->disableJwtMiddleware();

        $this->business = Business::query()->create([
            'code' => 'cua-hang-chinh',
            'name' => 'Cửa Hàng Chính',
            'email' => 'owner@slug-shop.local',
            'status' => 'active',
            'currency_code' => 'VND',
            'timezone' => 'Asia/Ho_Chi_Minh',
        ]);

        $this->owner = User::query()->create([
            'name' => 'Chủ Shop',
            'email' => 'owner@slug-shop.local',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $this->attachUserToBusiness($this->owner, $this->business->id);
        $this->actingAsBusinessUser($this->owner);

        $this->unit = Unit::query()->create([
            'business_id' => $this->business->id,
            'name' => 'Đơn vị chuẩn',
            'is_active' => true,
        ]);

        $this->warehouse = Warehouse::query()->create([
            'business_id' => $this->business->id,
            'name' => 'Kho Chính Hà Nội',
            'address' => 'Ha Noi',
            'is_active' => true,
        ]);

        $this->category = Category::query()->create([
            'business_id' => $this->business->id,
            'name' => 'Phụ kiện sạc',
            'is_active' => true,
        ]);

        $this->customer = Customer::query()->create([
            'business_id' => $this->business->id,
            'name' => 'Nguyễn Thị Hà',
            'phone' => '0901234567',
            'is_active' => true,
        ]);

        $this->supplier = Supplier::query()->create([
            'business_id' => $this->business->id,
            'name' => 'Nhà cung cấp điện tử',
            'phone' => '02838889999',
            'is_active' => true,
        ]);

        $this->product = Product::query()->create([
            'business_id' => $this->business->id,
            'unit_id' => $this->unit->id,
            'category_id' => $this->category->id,
            'sku' => 'SKU-SLUG-001',
            'name' => 'Sản phẩm sạc nhanh',
            'barcode' => '8938501999999',
            'cost_price' => 10000,
            'sale_price' => 15000,
            'status' => 'active',
        ]);
    }

    public function test_models_with_name_auto_generate_name_slug(): void
    {
        $this->assertDatabaseHas('businesses', [
            'id' => $this->business->id,
            'name_slug' => 'cua-hang-chinh',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->owner->id,
            'name_slug' => 'chu-shop',
        ]);

        $this->assertDatabaseHas('units', [
            'id' => $this->unit->id,
            'name_slug' => 'don-vi-chuan',
        ]);

        $this->assertDatabaseHas('warehouses', [
            'id' => $this->warehouse->id,
            'name_slug' => 'kho-chinh-ha-noi',
        ]);

        $this->assertDatabaseHas('categories', [
            'id' => $this->category->id,
            'name_slug' => 'phu-kien-sac',
        ]);

        $this->assertDatabaseHas('customers', [
            'id' => $this->customer->id,
            'name_slug' => 'nguyen-thi-ha',
        ]);

        $this->assertDatabaseHas('suppliers', [
            'id' => $this->supplier->id,
            'name_slug' => 'nha-cung-cap-dien-tu',
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $this->product->id,
            'name_slug' => 'san-pham-sac-nhanh',
        ]);
    }

    public function test_api_routes_can_search_name_without_diacritics(): void
    {
        $this->assertListContainsName('/api/units?name=don%20vi%20chuan', 'Đơn vị chuẩn');
        $this->assertListContainsName('/api/warehouses?name=kho%20chinh%20ha%20noi', 'Kho Chính Hà Nội');
        $this->assertListContainsName('/api/categories?name=phu%20kien%20sac', 'Phụ kiện sạc');
        $this->assertListContainsName('/api/customers?name=nguyen%20thi%20ha', 'Nguyễn Thị Hà');
        $this->assertListContainsName('/api/suppliers?name=nha%20cung%20cap%20dien%20tu', 'Nhà cung cấp điện tử');
        $this->assertListContainsName('/api/products?name=san%20pham%20sac%20nhanh', 'Sản phẩm sạc nhanh');
    }

    public function test_inventory_route_can_search_product_name_without_diacritics(): void
    {
        CurrentStock::query()->create([
            'business_id' => $this->business->id,
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'quantity_on_hand' => 10,
            'avg_unit_cost' => 10000,
            'stock_value' => 100000,
            'last_movement_at' => now(),
        ]);

        $response = $this->getJson('/api/inventory/stocks?product_name=san%20pham%20sac%20nhanh');

        $response->assertOk();

        $names = collect($response->json('data.items'))->pluck('product.name')->all();

        $this->assertContains('Sản phẩm sạc nhanh', $names);
    }

    public function test_user_index_can_search_name_without_diacritics(): void
    {
        $staff = User::query()->create([
            'name' => 'Nguyễn Văn Hùng',
            'email' => 'hung@slug-shop.local',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $this->attachUserToBusiness($staff, $this->business->id, role: 'staff', isOwner: false);

        $response = $this->getJson('/api/users?name=nguyen%20van%20hung');

        $response->assertOk();

        $emails = collect($response->json('data.items'))->pluck('email')->all();

        $this->assertContains('hung@slug-shop.local', $emails);
    }

    protected function assertListContainsName(string $uri, string $expectedName): void
    {
        $response = $this->getJson($uri);

        $response->assertOk();

        $names = collect($response->json('data.items'))->pluck('name')->all();

        $this->assertContains($expectedName, $names);
    }
}
