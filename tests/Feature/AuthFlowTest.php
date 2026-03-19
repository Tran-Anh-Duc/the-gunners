<?php

namespace Tests\Feature;

use App\Helpers\JwtHelper;
use App\Models\Business;
use App\Models\BusinessModule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\InteractsWithBusinessApi;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use InteractsWithBusinessApi;
    use RefreshDatabase;

    public function test_register_creates_owner_business_membership_and_default_modules(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Demo Owner',
            'email' => 'owner@example.com',
            'password' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.token_type', 'bearer')
            ->assertJsonPath('data.expires_in', 7200);

        $user = User::query()->where('email', 'owner@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('secret123', $user->password));

        $business = Business::query()->where('email', 'owner@example.com')->first();
        $this->assertNotNull($business);
        $this->assertSame('owner', $business->code);

        $this->assertDatabaseHas('business_users', [
            'business_id' => $business->id,
            'user_id' => $user->id,
            'role' => 'owner',
            'status' => 'active',
            'is_owner' => true,
        ]);

        $this->assertSame(
            6,
            BusinessModule::query()->where('business_id', $business->id)->count(),
        );

        $payload = JwtHelper::decodeToken($response->json('data.access_token'));
        $this->assertSame($user->id, $payload->sub);
        $this->assertSame($business->id, $payload->data->business_id);
        $this->assertSame('owner', $payload->data->role);
    }

    public function test_login_returns_a_token_for_an_active_user_with_an_active_membership(): void
    {
        $business = Business::query()->create([
            'code' => 'demo-shop',
            'name' => 'Demo Shop',
            'email' => 'owner@demo-shop.local',
            'status' => 'active',
            'currency_code' => 'VND',
            'timezone' => 'Asia/Ho_Chi_Minh',
        ]);

        $user = User::query()->create([
            'name' => 'Manager',
            'email' => 'manager@demo-shop.local',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $this->attachUserToBusiness($user, $business->id, role: 'manager', isOwner: false);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'manager@demo-shop.local',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.token_type', 'bearer')
            ->assertJsonPath('code', 'login_success');

        $payload = JwtHelper::decodeToken($response->json('data.access_token'));
        $this->assertSame($user->id, $payload->sub);
        $this->assertSame($business->id, $payload->data->business_id);
        $this->assertSame('manager', $payload->data->role);

        $user->refresh();
        $this->assertNotNull($user->last_login_at);
    }

    public function test_login_rejects_users_without_an_active_membership(): void
    {
        $user = User::query()->create([
            'name' => 'Inactive Member',
            'email' => 'inactive-member@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $business = Business::query()->create([
            'code' => 'inactive-shop',
            'name' => 'Inactive Shop',
            'email' => 'inactive-member@example.com',
            'status' => 'active',
            'currency_code' => 'VND',
            'timezone' => 'Asia/Ho_Chi_Minh',
        ]);

        $this->attachUserToBusiness($user, $business->id, status: 'inactive', isOwner: false);

        $this->postJson('/api/auth/login', [
            'email' => 'inactive-member@example.com',
            'password' => 'password',
        ])->assertStatus(422);
    }

    public function test_me_and_logout_respect_the_jwt_blacklist_flow(): void
    {
        $business = Business::query()->create([
            'code' => 'jwt-shop',
            'name' => 'JWT Shop',
            'email' => 'owner@jwt-shop.local',
            'status' => 'active',
            'currency_code' => 'VND',
            'timezone' => 'Asia/Ho_Chi_Minh',
        ]);

        $user = User::query()->create([
            'name' => 'JWT User',
            'email' => 'owner@jwt-shop.local',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $this->attachUserToBusiness($user, $business->id);

        $token = JwtHelper::generateToken($user->fresh('businessMemberships'));

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('email', 'owner@jwt-shop.local');

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logged out');

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/auth/me')
            ->assertStatus(401)
            ->assertJsonPath('error', 'Token is blacklisted');
    }
}
