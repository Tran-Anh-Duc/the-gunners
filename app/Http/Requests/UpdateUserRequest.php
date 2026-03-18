<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

/**
 * Validate cập nhật user và membership.
 *
 * Email vẫn unique trên toàn hệ thống,
 * còn `role`, `status`, `is_owner` là dữ liệu theo business.
 */
class UpdateUserRequest extends BaseBusinessRequest
{
    /**
     * Rule cập nhật user/membership.
     *
     * Email vẫn unique trên bảng `users` toàn hệ thống.
     * `role/status/is_owner` là dữ liệu của membership trong business.
     */
    public function rules(): array
    {
        return [
            'business_id' => $this->businessRules(),
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->route('id')),
            ],
            'password' => [
                'nullable',
                'string',
                'min:8',
            ],
            'phone' => [
                'nullable',
                'string',
                'max:20',
            ],
            'avatar' => [
                'nullable',
                'string',
                'max:255',
            ],
            'role' => [
                'nullable',
                'string',
                Rule::in(['owner', 'manager', 'staff']),
            ],
            'membership_status' => [
                'nullable',
                'string',
                Rule::in(['active', 'invited', 'inactive']),
            ],
            'is_owner' => [
                'nullable',
                'boolean',
            ],
            'is_active' => [
                'nullable',
                'boolean',
            ],
        ];
    }
}
