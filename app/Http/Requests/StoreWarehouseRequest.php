<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

/**
 * Validate tạo mới kho.
 *
 * cođể kho phải unique trong business để để trả cuu va sinh chứng từ sau này.
 */
class StoreWarehouseRequest extends BaseBusinessRequest
{
    public function rules(): array
    {
        $businessId = $this->integer('business_id');

        return [
            'business_id' => $this->businessRules(),
            'code' => ['required', 'string', 'max:50', Rule::unique('warehouses', 'code')->where(fn ($query) => $query->where('business_id', $businessId))],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive'])],
        ];
    }
}
