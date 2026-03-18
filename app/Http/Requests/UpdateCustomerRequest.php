<?php

namespace App\Http\Requests;

/**
 * Validate cập nhật khách hàng.
 *
 * Dung `sometimes` cho trường bat buoc để update partial để hon cho frontend.
 */
class UpdateCustomerRequest extends BaseBusinessRequest
{
    public function rules(): array
    {
        return [
            'business_id' => $this->businessRules(),
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
