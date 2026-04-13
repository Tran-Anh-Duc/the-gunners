<?php

namespace Tests\Feature;

use App\Http\Middleware\JwtMiddleware;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductCatalogFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Business $business;

    protected Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(JwtMiddleware::class);

        $this->business = Business::query()->create([
            'code' => 'test-shop',
            'name' => 'Test Shop',
            'email' => 'owner@test-shop.local',
            'status' => 'active',
            'currency_code' => 'VND',
            'timezone' => 'Asia/Ho_Chi_Minh',
        ]);

        $this->user = User::query()->create([
            'name' => 'Owner',
            'email' => 'owner@test-shop.local',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $membership = BusinessUser::query()->create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
            'status' => 'active',
            'is_owner' => true,
            'joined_at' => now(),
        ]);

        $this->unit = Unit::query()->create([
            'business_id' => $this->business->id,
            'name' => 'Cai',
            'is_active' => true,
        ]);

        Warehouse::query()->create([
            'business_id' => $this->business->id,
            'name' => 'Kho chinh',
            'is_active' => true,
        ]);

        app()->instance('jwt_user', $this->user->fresh());
        app()->instance('jwt_active_membership', $membership);
        app()->instance('jwt_business_id', $this->business->id);

        auth()->setUser($this->user);
        $this->actingAs($this->user);
    }

    public function test_it_creates_a_product_in_the_current_business_scope(): void
    {
        $response = $this->postJson('/api/products', [
            'sku' => 'SKU-TEST-001',
            'name' => 'San pham test',
            'unit_id' => $this->unit->id,
            'cost_price' => 10000,
            'sale_price' => 15000,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.business_id', $this->business->id)
            ->assertJsonPath('data.sku', 'SKU-TEST-001');

        $this->assertDatabaseHas('products', [
            'business_id' => $this->business->id,
            'sku' => 'SKU-TEST-001',
        ]);
    }

    public function test_it_auto_generates_stable_sku_when_frontend_does_not_send_one(): void
    {
        $firstResponse = $this->postJson('/api/products', [
            'name' => 'San pham tu sinh SKU 1',
            'unit_id' => $this->unit->id,
            'cost_price' => 10000,
            'sale_price' => 15000,
        ]);

        $secondResponse = $this->postJson('/api/products', [
            'name' => 'San pham tu sinh SKU 2',
            'unit_id' => $this->unit->id,
            'cost_price' => 12000,
            'sale_price' => 18000,
        ]);

        $firstResponse->assertOk();
        $secondResponse->assertOk();

        $firstSku = (string) $firstResponse->json('data.sku');
        $secondSku = (string) $secondResponse->json('data.sku');

        $this->assertMatchesRegularExpression('/^TEST-SHOP-[A-Z]{3}-000001$/', $firstSku);
        $this->assertMatchesRegularExpression('/^TEST-SHOP-[A-Z]{3}-000002$/', $secondSku);

        preg_match('/^TEST-SHOP-([A-Z]{3})-000001$/', $firstSku, $matches);

        $this->assertSame(sprintf('TEST-SHOP-%s-000002', $matches[1]), $secondSku);
        $this->assertDatabaseHas('business_sequences', [
            'business_id' => $this->business->id,
            'scope' => 'product.sku',
            'prefix' => $matches[1],
            'current_value' => 2,
        ]);
    }

    public function test_it_rejects_updating_sku_after_product_creation(): void
    {
        $product = Product::query()->create([
            'business_id' => $this->business->id,
            'unit_id' => $this->unit->id,
            'sku' => 'SKU-'.Str::upper(Str::random(6)),
            'name' => 'San pham test',
            'product_type' => 'simple',
            'track_inventory' => true,
            'cost_price' => 10000,
            'sale_price' => 15000,
            'is_active' => true,
        ]);

        $this->putJson("/api/products/{$product->id}", [
            'sku' => 'SKU-MOI-001',
            'name' => 'Ten moi',
        ])->assertStatus(422)
            ->assertJsonPath('code', 'error_failed')
            ->assertJsonStructure(['data' => ['sku']]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'sku' => $product->sku,
        ]);
    }
}
