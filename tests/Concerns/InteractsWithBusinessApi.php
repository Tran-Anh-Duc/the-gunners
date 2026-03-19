<?php

namespace Tests\Concerns;

use App\Http\Middleware\JwtMiddleware;
use App\Models\BusinessUser;
use App\Models\User;

trait InteractsWithBusinessApi
{
    protected function disableJwtMiddleware(): void
    {
        $this->withoutMiddleware(JwtMiddleware::class);
    }

    protected function actingAsBusinessUser(User $user): void
    {
        $freshUser = $user->fresh('activeBusinessMemberships');
        $membership = $freshUser?->activeBusinessMemberships->first();

        app()->instance('jwt_user', $freshUser);

        if ($membership) {
            app()->instance('jwt_active_membership', $membership);
            app()->instance('jwt_business_id', (int) $membership->business_id);
        }

        auth()->setUser($user);
        $this->actingAs($user);
    }

    protected function attachUserToBusiness(
        User $user,
        int $businessId,
        string $role = 'owner',
        string $status = 'active',
        bool $isOwner = true,
    ): BusinessUser {
        return BusinessUser::query()->create([
            'business_id' => $businessId,
            'user_id' => $user->id,
            'role' => $role,
            'status' => $status,
            'is_owner' => $isOwner,
            'joined_at' => now(),
        ]);
    }
}
