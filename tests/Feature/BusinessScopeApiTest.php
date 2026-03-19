<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Customer;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\InteractsWithBusinessApi;
use Tests\TestCase;

class BusinessScopeApiTest extends TestCase
{
    use InteractsWithBusinessApi;
    use RefreshDatabase;

    protected Business $business;

    protected User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->disableJwtMiddleware();

        $this->business = Business::query()->create([
            'code' => 'scope-shop',
            'name' => 'Scope Shop',
            'email' => 'owner@scope-shop.local',
            'status' => 'active',
            'currency_code' => 'VND',
            'timezone' => 'Asia/Ho_Chi_Minh',
        ]);

        $this->owner = User::query()->create([
            'name' => 'Owner',
            'email' => 'owner@scope-shop.local',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $this->attachUserToBusiness($this->owner, $this->business->id);
        $this->actingAsBusinessUser($this->owner);
    }

    public function test_product_creation_rejects_a_unit_from_another_business(): void
    {
        $foreignBusiness = Business::query()->create([
            'code' => 'foreign-shop',
            'name' => 'Foreign Shop',
            'email' => 'owner@foreign-shop.local',
            'status' => 'active',
            'currency_code' => 'VND',
            'timezone' => 'Asia/Ho_Chi_Minh',
        ]);

        $foreignUnit = Unit::query()->create([
            'business_id' => $foreignBusiness->id,
            'code' => 'BOX',
            'name' => 'Box',
            'is_active' => true,
        ]);

        $this->postJson('/api/products', [
            'sku' => 'SKU-FOREIGN-UNIT',
            'name' => 'Sai business unit',
            'unit_id' => $foreignUnit->id,
            'cost_price' => 10000,
            'sale_price' => 15000,
        ])->assertStatus(422)
            ->assertJsonPath('message', 'The selected value is invalid for the current business.');
    }

    public function test_show_endpoint_returns_404_for_a_record_outside_the_current_business(): void
    {
        $foreignBusiness = Business::query()->create([
            'code' => 'foreign-customer-shop',
            'name' => 'Foreign Customer Shop',
            'email' => 'owner@foreign-customer-shop.local',
            'status' => 'active',
            'currency_code' => 'VND',
            'timezone' => 'Asia/Ho_Chi_Minh',
        ]);

        $foreignCustomer = Customer::query()->create([
            'business_id' => $foreignBusiness->id,
            'name' => 'Khach ngoai scope',
            'phone' => '0900000001',
            'is_active' => true,
        ]);

        $this->getJson("/api/customers/{$foreignCustomer->id}")
            ->assertStatus(404);
    }

    public function test_user_index_only_lists_memberships_from_the_current_business(): void
    {
        $currentBusinessUser = User::query()->create([
            'name' => 'Scoped User',
            'email' => 'scoped@scope-shop.local',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $this->attachUserToBusiness($currentBusinessUser, $this->business->id, role: 'staff', isOwner: false);

        $foreignBusiness = Business::query()->create([
            'code' => 'scope-shop-foreign',
            'name' => 'Scope Shop Foreign',
            'email' => 'owner@scope-shop-foreign.local',
            'status' => 'active',
            'currency_code' => 'VND',
            'timezone' => 'Asia/Ho_Chi_Minh',
        ]);

        $foreignUser = User::query()->create([
            'name' => 'Foreign User',
            'email' => 'foreign@scope-shop.local',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $this->attachUserToBusiness($foreignUser, $foreignBusiness->id, role: 'staff', isOwner: false);

        $response = $this->getJson('/api/users');

        $response->assertOk()
            ->assertJsonPath('data.total', 2);

        $emails = collect($response->json('data.items'))->pluck('email')->all();

        $this->assertContains('owner@scope-shop.local', $emails);
        $this->assertContains('scoped@scope-shop.local', $emails);
        $this->assertNotContains('foreign@scope-shop.local', $emails);
    }
}
