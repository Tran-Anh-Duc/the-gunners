<?php

namespace App\Http\Requests;

/**
 * Validate tạo mới đơn vì tinh.
 *
 * code đơn vị tính là mã nội bộ do hệ thống tự sinh, frontend không được nhập tay.
 */
class StoreUnitRequest extends BaseBusinessRequest
{
    public function rules(): array
    {
        return [
            'business_id' => $this->businessRules(),
            'code' => ['prohibited'],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
