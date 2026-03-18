<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Request validate register public.
 *
 * Co y chỉ mo cac field toi thieu để tránh public API ghi nham field quản trị
 * nhu role, is_owner, business_id...
 */
class RegisterUserRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Rule register public.
     *
     * Chi mo 3 field:
     * - name
     * - email
     * - password
     *
     * Vi toan bo field quản trị/business se do AuthService từ sinh.
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
            ],
        ];
    }

    /**
     * Message lỗi custom cho màn hình register.
     */
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
