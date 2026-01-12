<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreUserRequest extends BaseFormRequest
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
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                //'confirmed', // cần password_confirmation
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('validation.user.name.required'),
            'name.max' => __('validation.user.name.max'),

            'email.required' => __('validation.user.email.required'),
            'email.email' => __('validation.user.email.email'),
            'email.unique' => __('validation.user.email.unique'),

            'password.required' => __('validation.user.password.required'),
            'password.min' => __('validation.user.password.min'),
            'password.confirmed' => __('validation.user.password.confirmed'),
        ];
    }


}
