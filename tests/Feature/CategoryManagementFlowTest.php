<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Category;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\InteractsWithBusinessApi;
use Tests\TestCase;

class CategoryManagementFlowTest extends TestCase
{
    use InteractsWithBusinessApi;
    use RefreshDatabase;

    protected Business $business;

    protected User $owner;

    protected Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->disableJwtMiddleware();

        $this->business = Business::query()->create([
            'code' => 'category-shop',
            'name' => 'Category Shop',
            'email' => 'owner@category-shop.local',
            'status' => 'active',
            'currency_code' => 'VND',
            'timezone' => 'Asia/Ho_Chi_Minh',
        ]);

        $this->owner = User::query()->create([
            'name' => 'Owner',
            'email' => 'owner@category-shop.local',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $this->unit = Unit::query()->create([
            'business_id' => $this->business->id,
            'name' => 'Cai',
            'is_active' => true,
        ]);

        $this->attachUserToBusiness($this->owner, $this->business->id);
        $this->actingAsBusinessUser($this->owner);
    }

    public function test_category_crud_works_in_the_current_business(): void
    {
        $createResponse = $this->postJson('/api/categories', [
            'name' => 'Phu kien dien thoai',
            'description' => 'Nhom phu kien ban kem',
        ]);

        $createResponse->assertOk()
            ->assertJsonPath('data.name', 'Phu kien dien thoai')
            ->assertJsonPath('data.is_active', true);

        $categoryId = $createResponse->json('data.id');

        $this->getJson("/api/categories/{$categoryId}")
            ->assertOk()
            ->assertJsonPath('data.id', $categoryId);

        $this->putJson("/api/categories/{$categoryId}", [
            'name' => 'Phu kien cap nhat',
            'is_active' => false,
        ])->assertOk()
            ->assertJsonPath('data.name', 'Phu kien cap nhat')
            ->assertJsonPath('data.is_active', false);

        $this->deleteJson("/api/categories/{$categoryId}")
            ->assertOk()
            ->assertJsonPath('data.id', $categoryId);

        $this->assertSoftDeleted('categories', ['id' => $categoryId]);
    }

    public function test_product_creation_persists_category_relation(): void
    {
        $category = Category::query()->create([
            'business_id' => $this->business->id,
            'name' => 'Am thanh',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/products', [
            'unit_id' => $this->unit->id,
            'category_id' => $category->id,
            'sku' => 'SKU-CATEGORY-001',
            'name' => 'Tai nghe test',
            'cost_price' => 10000,
            'sale_price' => 15000,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.category_id', $category->id)
            ->assertJsonPath('data.category.id', $category->id)
            ->assertJsonPath('data.category.name', 'Am thanh');
    }
}
