<?php

namespace App\Http\Requests;

/**
 * Validate tạo mới nhà cùng cấp.
 *
 * Supplier được dùng cho nhập kho va chỉ tien, nen thông tin lien he cần ro rang.
 */
class StoreSupplierRequest extends BaseBusinessRequest
{
    public function rules(): array
    {
        return [
            'business_id' => $this->businessRules(),
            'name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
