<?php

namespace App\Http\Requests;

/**
 * Validate cập nhật kho.
 *
 * code kho là mã nội bộ; request cập nhật không cho phép đổi bằng tay.
 */
class UpdateWarehouseRequest extends BaseBusinessRequest
{
    public function rules(): array
    {
        return [
            'business_id' => $this->businessRules(),
            'code' => ['prohibited'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'status' => ['prohibited'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
