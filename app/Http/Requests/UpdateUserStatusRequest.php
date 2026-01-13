<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateUserStatusRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],
            'description' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }


    public function messages(): array
    {
        return [
            // name
            'name.required' => __('validation.user_status.name.required'),
            'name.string'   => __('validation.user_status.name.string'),
            'name.max'      => __('validation.user_status.name.max'),

            // description
            'description.string' => __('validation.user_status.description.string'),
            'description.max'    => __('validation.user_status.description.max'),
        ];
    }



}
