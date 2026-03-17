<?php

namespace App\Http\Requests;

class BusinessActionRequest extends BaseBusinessRequest
{
    public function rules(): array
    {
        return [
            'business_id' => $this->businessRules(),
        ];
    }
}
