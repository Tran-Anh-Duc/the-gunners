<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class StoreUnitRequest extends BaseBusinessRequest
{
    public function rules(): array
    {
        $businessId = $this->integer('business_id');

        return [
            'business_id' => $this->businessRules(),
            'code' => ['required', 'string', 'max:20', Rule::unique('units', 'code')->where(fn ($query) => $query->where('business_id', $businessId))],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
