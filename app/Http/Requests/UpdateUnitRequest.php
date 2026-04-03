<?php

namespace App\Http\Requests;

/**
 * Validate cập nhật đơn vì tinh.
 *
 * code đơn vị tính là mã nội bộ; request cập nhật không cho phép đổi bằng tay.
 */
class UpdateUnitRequest extends BaseBusinessRequest
{
    public function rules(): array
    {
        return [
            'business_id' => $this->businessRules(),
            'code' => ['prohibited'],
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
