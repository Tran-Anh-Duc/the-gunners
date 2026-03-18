<?php

namespace App\Http\Requests;

/**
 * Validate tạo mới khách hàng.
 *
 * Customer MVP được giữ đơn gian: thông tin lien he + trạng thái hoat dòng.
 */
class StoreCustomerRequest extends BaseBusinessRequest
{
    public function rules(): array
    {
        return [
            'business_id' => $this->businessRules(),
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
