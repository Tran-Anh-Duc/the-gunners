<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\InteractsWithBusinessApi;
use Tests\TestCase;

class UserManagementFlowTest extends TestCase
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
            'code' => 'team-shop',
            'name' => 'Team Shop',
            'email' => 'owner@team-shop.local',
            'status' => 'active',
            'currency_code' => 'VND',
            'timezone' => 'Asia/Ho_Chi_Minh',
        ]);

        $this->owner = User::query()->create([
            'name' => 'Owner',
            'email' => 'owner@team-shop.local',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $this->attachUserToBusiness($this->owner, $this->business->id);
        $this->actingAsBusinessUser($this->owner);
    }

    public function test_store_creates_a_user_and_membership_in_the_current_business(): void
    {
        $response = $this->postJson('/api/auth/users', [
            'name' => 'Staff 01',
            'email' => 'staff01@team-shop.local',
            'password' => 'secret123',
            'role' => 'staff',
            'membership_status' => 'active',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.email', 'staff01@team-shop.local')
            ->assertJsonPath('data.business_id', $this->business->id)
            ->assertJsonPath('data.role', 'staff');

        $user = User::query()->where('email', 'staff01@team-shop.local')->first();
        $this->assertNotNull($user);
        $this->assertDatabaseHas('business_users', [
            'business_id' => $this->business->id,
            'user_id' => $user->id,
            'role' => 'staff',
            'status' => 'active',
        ]);
    }

    public function test_update_changes_both_user_attributes_and_business_membership(): void
    {
        $user = User::query()->create([
            'name' => 'Manager 01',
            'email' => 'manager01@team-shop.local',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $this->attachUserToBusiness($user, $this->business->id, role: 'staff', isOwner: false);

        $response = $this->putJson("/api/auth/users/{$user->id}", [
            'name' => 'Manager Updated',
            'phone' => '0909123456',
            'role' => 'manager',
            'membership_status' => 'inactive',
            'is_owner' => false,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Manager Updated')
            ->assertJsonPath('data.role', 'manager')
            ->assertJsonPath('data.membership_status', 'inactive');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Manager Updated',
            'phone' => '0909123456',
        ]);

        $this->assertDatabaseHas('business_users', [
            'business_id' => $this->business->id,
            'user_id' => $user->id,
            'role' => 'manager',
            'status' => 'inactive',
            'is_owner' => false,
        ]);
    }

    public function test_destroy_only_removes_the_current_membership_when_user_belongs_to_multiple_businesses(): void
    {
        $secondBusiness = Business::query()->create([
            'code' => 'team-shop-2',
            'name' => 'Team Shop 2',
            'email' => 'owner@team-shop-2.local',
            'status' => 'active',
            'currency_code' => 'VND',
            'timezone' => 'Asia/Ho_Chi_Minh',
        ]);

        $user = User::query()->create([
            'name' => 'Shared User',
            'email' => 'shared@team-shop.local',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $this->attachUserToBusiness($user, $this->business->id, role: 'staff', isOwner: false);
        $this->attachUserToBusiness($user, $secondBusiness->id, role: 'manager', isOwner: false);

        $this->deleteJson("/api/auth/users/{$user->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $user->id);

        $this->assertDatabaseMissing('business_users', [
            'business_id' => $this->business->id,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('business_users', [
            'business_id' => $secondBusiness->id,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'shared@team-shop.local',
        ]);
    }
}
