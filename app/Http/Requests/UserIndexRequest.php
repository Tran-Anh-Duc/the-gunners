<?php

namespace App\Http\Requests;

/**
 * Validate bộ lọc danh sách user.
 *
 * Cho phép lọc cả theo field user (`name`, `email`, `phone`)
 * và field membership (`role`, `membership_status`).
 */
class UserIndexRequest extends BaseBusinessRequest
{
    /**
     * Rule cho màn hình danh sách user trong business hiện tại.
     */
    public function rules(): array
    {
        return [
            'business_id' => $this->businessRules(),
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['nullable', 'string'],
            'membership_status' => ['nullable', 'string'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort_by' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'in:asc,desc'],
        ];
    }
}
