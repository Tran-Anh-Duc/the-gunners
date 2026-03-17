<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateUnitRequest extends BaseBusinessRequest
{
    public function rules(): array
    {
        $businessId = $this->integer('business_id');
        $id = (int) $this->route('id');

        return [
            'business_id' => $this->businessRules(),
            'code' => ['sometimes', 'required', 'string', 'max:20', Rule::unique('units', 'code')->ignore($id)->where(fn ($query) => $query->where('business_id', $businessId))],
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
