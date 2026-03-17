<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateWarehouseRequest extends BaseBusinessRequest
{
    public function rules(): array
    {
        $businessId = $this->integer('business_id');
        $id = (int) $this->route('id');

        return [
            'business_id' => $this->businessRules(),
            'code' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('warehouses', 'code')->ignore($id)->where(fn ($query) => $query->where('business_id', $businessId))],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive'])],
        ];
    }
}
