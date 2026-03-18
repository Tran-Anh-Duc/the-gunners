<?php

namespace App\Http\Requests;

/**
 * Validate cập nhật nhà cùng cấp.
 *
 * Cho phep update partial nhưng van giữ cac rule co ban ve email/phone.
 */
class UpdateSupplierRequest extends BaseBusinessRequest
{
    public function rules(): array
    {
        return [
            'business_id' => $this->businessRules(),
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
