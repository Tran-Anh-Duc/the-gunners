<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Request validate login public.
 *
 * Không dùng business scope vì login xảy ra trước khi hệ thống biết user đang vào business nào.
 */
class LoginUserRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Rule login tối thiểu:
     * - `email`: để tìm user hệ thống
     * - `password`: để xác thực
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email',
            ],
            'password' => [
                'required',
            ]
        ];
    }

    /**
     * Message lỗi thân thiện cho frontend.
     */
    public function messages(): array
    {
        return [
            'email.required' => __('validation.user.email.required'),
            'email.email' => __('validation.user.email.email'),

            'password.required' => __('validation.user.password.required'),
        ];
    }
}
