<?php

namespace App\Services;

use App\Repositories\UserRepository;
use App\Support\BusinessContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly BusinessContext $businessContext,
    ) {
    }

    public function listQuery(array $filters): Builder
    {
        $businessId = $this->resolveBusinessId($filters);

        return $this->userRepository->queryForBusiness($businessId, $filters);
    }

    public function show(int $id, array $data): Model
    {
        $businessId = $this->resolveBusinessId($data);

        return $this->loadUserRelations(
            $this->userRepository->findForBusinessOrFail($businessId, $id),
            $businessId,
        );
    }

    public function create(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $businessId = $this->resolveBusinessId($data);
            $userData = $this->extractUserAttributes($data, true);
            $userData['password'] = Hash::make($userData['password']);
            $userData['is_active'] = $userData['is_active'] ?? true;

            $user = $this->userRepository->create($userData);
            $this->userRepository->createMembership([
                'business_id' => $businessId,
                'user_id' => $user->id,
                'role' => $data['role'] ?? 'staff',
                'status' => $data['membership_status'] ?? 'active',
                'is_owner' => (bool) ($data['is_owner'] ?? false),
                'joined_at' => now(),
            ]);

            return $this->loadUserRelations($user, $businessId);
        });
    }

    public function update(int $id, array $data): Model
    {
        return DB::transaction(function () use ($id, $data) {
            $businessId = $this->resolveBusinessId($data);
            $user = $this->userRepository->findForBusiness($businessId, $id);

            if (! $user) {
                abort(404);
            }

            $userData = $this->extractUserAttributes($data, true);

            if (array_key_exists('password', $userData)) {
                $userData['password'] = Hash::make($userData['password']);
            }

            if ($userData !== []) {
                $this->userRepository->updateRecord($user, $userData);
            }

            $membershipData = Arr::only($data, ['role', 'membership_status', 'is_owner']);
            $normalizedMembership = [];

            if (array_key_exists('role', $membershipData)) {
                $normalizedMembership['role'] = $membershipData['role'];
            }

            if (array_key_exists('membership_status', $membershipData)) {
                $normalizedMembership['status'] = $membershipData['membership_status'];
            }

            if (array_key_exists('is_owner', $membershipData)) {
                $normalizedMembership['is_owner'] = (bool) $membershipData['is_owner'];
            }

            if ($normalizedMembership !== []) {
                $membership = $this->userRepository->findMembershipForBusiness($user, $businessId);
                $this->userRepository->updateMembership($membership, $normalizedMembership);
            }

            return $this->loadUserRelations($user, $businessId);
        });
    }

    public function delete(int $id, array $data): Model
    {
        return DB::transaction(function () use ($id, $data) {
            $businessId = $this->resolveBusinessId($data);
            $user = $this->userRepository->findForBusiness($businessId, $id);

            if (! $user) {
                abort(404);
            }

            $this->userRepository->deleteMembershipForBusiness($user, $businessId);

            if (! $this->userRepository->hasMemberships($user)) {
                $this->userRepository->deleteRecord($user);
            }

            return $user;
        });
    }

    protected function resolveBusinessId(array $data = []): int
    {
        return $this->businessContext->resolveBusinessId(isset($data['business_id']) ? (int) $data['business_id'] : null);
    }

    protected function loadUserRelations(Model $user, int $businessId): Model
    {
        return $user->fresh([
            'businessMemberships' => function ($membershipQuery) use ($businessId) {
                $membershipQuery->where('business_id', $businessId)->with('business');
            },
        ]) ?? $user;
    }

    protected function extractUserAttributes(array $data, bool $allowManagementFields = false): array
    {
        $fields = ['name', 'email', 'password'];

        if ($allowManagementFields) {
            $fields = array_merge($fields, ['phone', 'avatar', 'is_active']);
        }

        $userData = Arr::only($data, $fields);

        if (array_key_exists('password', $userData) && blank($userData['password'])) {
            unset($userData['password']);
        }

        return $userData;
    }
}
