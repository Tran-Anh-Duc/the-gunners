<?php

namespace App\Transformers;

use App\Models\User;
use League\Fractal\TransformerAbstract;

/**
 * Transformer user.
 *
 * Gộp thông tin user hệ thống và membership trong business hiện tại
 * thành một payload gọn để frontend dùng trực tiếp.
 */
class UserTransform extends TransformerAbstract
{
    protected array $defaultIncludes = [];

    protected array $availableIncludes = [];

    public function transform(User $entry): array
    {
        // Controller và service đã eager load membership đúng business để transformer không phải query thêm.
        $membership = $entry->relationLoaded('businessMemberships')
            ? $entry->businessMemberships->first()
            : null;
        $business = $membership?->business;
        $businessId = $entry->getAttribute('membership_business_id') ?? $membership?->business_id;
        $businessName = $entry->getAttribute('membership_business_name')
            ?? $business?->name
            ?? (app()->bound('jwt_business_name') ? app('jwt_business_name') : '');
        $role = $entry->getAttribute('membership_role') ?? $membership?->role;
        $membershipStatus = $entry->getAttribute('membership_status') ?? $membership?->status;
        $isOwner = array_key_exists('membership_is_owner', $entry->getAttributes())
            ? (bool) $entry->getAttribute('membership_is_owner')
            : (bool) ($membership?->is_owner ?? false);
        $joinedAt = $entry->getAttribute('membership_joined_at') ?? $membership?->joined_at;

        return [
            'id' => $entry->id,
            'business_id' => $businessId,
            'business_name' => $businessName,
            'name' => $entry->name,
            'email' => $entry->email,
            'phone' => $entry->phone,
            'avatar' => $entry->avatar,
            'is_active' => (bool) $entry->is_active,
            'role' => $role,
            'membership_status' => $membershipStatus,
            'is_owner' => $isOwner,
            'joined_at' => $joinedAt,
            'last_login_at' => $entry->last_login_at,
        ];
    }
}
