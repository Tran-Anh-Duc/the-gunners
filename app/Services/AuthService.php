<?php

namespace App\Services;

use App\Helpers\JwtHelper;
use App\Models\Business;
use App\Models\BusinessModule;
use App\Models\BusinessUser;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(private readonly UserRepository $userRepository)
    {
    }

    public function register(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $user = $this->userRepository->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'is_active' => true,
            ]);

            $business = $this->createBusinessForOwner($user, $data);
            $this->userRepository->createMembership([
                'business_id' => $business->id,
                'user_id' => $user->id,
                'role' => 'owner',
                'status' => 'active',
                'is_owner' => true,
                'joined_at' => now(),
            ]);

            $this->seedDefaultModules($business->id);

            return [
                'access_token' => JwtHelper::generateToken($user->fresh('businessMemberships')),
                'token_type' => 'bearer',
                'expires_in' => JwtHelper::ttl(),
            ];
        });
    }

    public function login(array $data): array
    {
        $user = $this->userRepository->findByEmail($data['email']);

        if (
            ! $user
            || ! Hash::check($data['password'], $user->password)
            || ! $user->is_active
            || ! $user->activeBusinessMemberships()->exists()
        ) {
            throw ValidationException::withMessages([
                'email' => __('messages.user.user_login_failed'),
            ]);
        }

        $this->userRepository->touchLastLogin($user);

        return [
            'access_token' => JwtHelper::generateToken($user->fresh('businessMemberships')),
            'token_type' => 'bearer',
            'expires_in' => JwtHelper::ttl(),
        ];
    }

    protected function createBusinessForOwner(User $user, array $data): Business
    {
        $businessName = trim((string) ($data['business_name'] ?? ($user->name.' Shop')));
        $baseCode = Str::slug((string) Str::before($user->email, '@'));
        $baseCode = $baseCode !== '' ? $baseCode : 'shop-'.$user->id;
        $code = $baseCode;
        $suffix = 1;

        while (Business::query()->where('code', $code)->exists()) {
            $suffix++;
            $code = "{$baseCode}-{$suffix}";
        }

        return Business::query()->create([
            'code' => $code,
            'name' => $businessName,
            'email' => $user->email,
            'phone' => $user->phone,
            'plan_code' => 'starter',
            'status' => 'active',
            'currency_code' => 'VND',
            'timezone' => 'Asia/Ho_Chi_Minh',
        ]);
    }

    protected function seedDefaultModules(int $businessId): void
    {
        foreach (['products', 'inventory', 'orders', 'customers', 'suppliers', 'payments'] as $moduleCode) {
            BusinessModule::query()->firstOrCreate(
                [
                    'business_id' => $businessId,
                    'module_code' => $moduleCode,
                ],
                [
                    'status' => 'active',
                    'starts_at' => now(),
                    'ends_at' => null,
                ]
            );
        }
    }
}
