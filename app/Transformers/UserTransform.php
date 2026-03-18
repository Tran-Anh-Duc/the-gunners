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
        $membership = $entry->businessMemberships->first();
        $business = $membership?->business;

        return [
            'id' => $entry->id,
            'business_id' => $membership?->business_id,
            'business_name' => $business?->name ?? '',
            'name' => $entry->name,
            'email' => $entry->email,
            'phone' => $entry->phone,
            'avatar' => $entry->avatar,
            'is_active' => (bool) $entry->is_active,
            'role' => $membership?->role ?? null,
            'membership_status' => $membership?->status ?? null,
            'is_owner' => (bool) ($membership?->is_owner ?? false),
            'joined_at' => $membership?->joined_at,
            'last_login_at' => $entry->last_login_at,
        ];
    }
}
