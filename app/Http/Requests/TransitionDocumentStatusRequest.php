<?php

namespace App\Http\Requests;

class TransitionDocumentStatusRequest extends BaseBusinessRequest
{
    public function rules(): array
    {
        return [
            'business_id' => $this->businessRules(),
        ];
    }
}
