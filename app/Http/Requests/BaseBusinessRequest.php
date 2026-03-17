<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

abstract class BaseBusinessRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $businessId = $this->input('business_id', $this->query('business_id'));

        if ($businessId === null && app()->bound('jwt_user')) {
            $businessId = app('jwt_user')->activeBusinessMemberships()->value('business_id');
        }

        if ($businessId !== null) {
            $this->merge(['business_id' => (int) $businessId]);
        }
    }

    protected function businessRules(bool $required = false): array
    {
        return [
            $required ? 'required' : 'nullable',
            'integer',
            Rule::exists('businesses', 'id'),
        ];
    }
}
