<?php

namespace App\Repositories;

use App\Models\BusinessUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class UserRepository extends BaseRepository
{
    public function __construct(User $user)
    {
        $this->model = $user;
    }

    public function getModel()
    {
        return User::class;
    }

    public function findByEmail(string $email): ?User
    {
        return User::query()->where('email', $email)->first();
    }

    public function touchLastLogin(User $user): void
    {
        $user->update(['last_login_at' => now()]);
    }

    public function queryForBusiness(int $businessId, array $filters = []): Builder
    {
        $query = User::query()
            ->whereHas('businessMemberships', function (Builder $membershipQuery) use ($businessId, $filters) {
                $membershipQuery->where('business_id', $businessId);

                if (! empty($filters['role'])) {
                    $membershipQuery->where('role', $filters['role']);
                }

                if (! empty($filters['membership_status'])) {
                    $membershipQuery->where('status', $filters['membership_status']);
                }
            })
            ->with(['businessMemberships' => function ($membershipQuery) use ($businessId) {
                $membershipQuery->where('business_id', $businessId)->with('business');
            }]);

        if (! empty($filters['name'])) {
            $query->where('name', 'like', '%'.$filters['name'].'%');
        }

        if (! empty($filters['email'])) {
            $query->where('email', 'like', '%'.$filters['email'].'%');
        }

        if (! empty($filters['phone'])) {
            $query->where('phone', 'like', '%'.$filters['phone'].'%');
        }

        return $query->orderByDesc('id');
    }

    public function findForBusiness(int $businessId, int $id): ?User
    {
        return User::query()
            ->whereKey($id)
            ->whereHas('businessMemberships', function (Builder $membershipQuery) use ($businessId) {
                $membershipQuery->where('business_id', $businessId);
            })
            ->first();
    }

    public function findForBusinessOrFail(int $businessId, int $id): User
    {
        return User::query()
            ->whereKey($id)
            ->whereHas('businessMemberships', function (Builder $membershipQuery) use ($businessId) {
                $membershipQuery->where('business_id', $businessId);
            })
            ->firstOrFail();
    }

    public function updateRecord(User $user, array $attributes): User
    {
        $user->update($attributes);

        return $user;
    }

    public function createMembership(array $attributes): BusinessUser
    {
        return BusinessUser::query()->create($attributes);
    }

    public function findMembershipForBusiness(User $user, int $businessId): BusinessUser
    {
        return $user->businessMemberships()
            ->where('business_id', $businessId)
            ->firstOrFail();
    }

    public function updateMembership(BusinessUser $membership, array $attributes): BusinessUser
    {
        $membership->update($attributes);

        return $membership;
    }

    public function deleteMembershipForBusiness(User $user, int $businessId): void
    {
        $user->businessMemberships()->where('business_id', $businessId)->delete();
    }

    public function hasMemberships(User $user): bool
    {
        return $user->businessMemberships()->exists();
    }

    public function deleteRecord(User $user): void
    {
        $user->delete();
    }
}
