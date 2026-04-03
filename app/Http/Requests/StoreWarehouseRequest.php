<?php

namespace App\Http\Requests;

/**
 * Validate tạo mới kho.
 *
 * code kho là mã nội bộ do hệ thống tự sinh, frontend không được nhập tay.
 */
class StoreWarehouseRequest extends BaseBusinessRequest
{
    public function rules(): array
    {
        return [
            'business_id' => $this->businessRules(),
            'code' => ['prohibited'],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'status' => ['prohibited'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
