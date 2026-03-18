<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

/**
 * Validate tạo user trong business hiện tại.
 *
 * Request này gom hai lớp dữ liệu:
 * - thông tin tài khoản user;
 * - thông tin membership trong business.
 */
class StoreUserRequest extends BaseBusinessRequest
{
    /**
     * Rule tạo user + membership.
     *
     * Nhóm field user:
     * - name, email, password, phone, avatar, is_active
     *
     * Nhóm field membership:
     * - role
     * - membership_status
     * - is_owner
     */
    public function rules(): array
    {
        return [
            'business_id' => $this->businessRules(),
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'password' => [
                'required',
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
