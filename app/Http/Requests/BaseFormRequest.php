<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Traits\ApiResponse; // nếu ApiResponse ở đây

abstract class BaseFormRequest extends FormRequest
{
    use ApiResponse;

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        throw new HttpResponseException(
            $this->errorResponse(

                __('messages.validation_error'), // ví dụ key trong lang
                '',
                422,
                $validator->errors()
            )
        );
    }
}
