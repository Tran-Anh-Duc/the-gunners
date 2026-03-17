<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Validation\ValidationException;

class BusinessContext
{
    public function currentUser(): ?User
    {
        if (app()->bound('jwt_user')) {
            /** @var User $user */
            $user = app('jwt_user');

            return $user;
        }

        return auth()->user();
    }

    public function resolveBusinessId(?int $requestedBusinessId = null): int
    {
        $user = $this->currentUser();

        if ($user) {
            if ($requestedBusinessId !== null) {
                $hasAccess = $user->activeBusinessMemberships()
                    ->where('business_id', $requestedBusinessId)
                    ->exists();

                if (! $hasAccess) {
                    throw ValidationException::withMessages([
                        'business_id' => 'Business not found or inaccessible.',
                    ]);
                }

                return $requestedBusinessId;
            }

            $defaultBusinessId = $user->activeBusinessMemberships()->value('business_id');

            if ($defaultBusinessId !== null) {
                return (int) $defaultBusinessId;
            }
        }

        if ($requestedBusinessId !== null) {
            return $requestedBusinessId;
        }

        throw ValidationException::withMessages([
            'business_id' => 'Business context is required.',
        ]);
    }
}
