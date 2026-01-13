<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreUserStatusRequest extends BaseFormRequest
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
            'slug' => [
                'required',
                'string',
                'max:255',
                'unique:users_status,slug',
                'regex:/^[a-z0-9_]+$/'
            ],
            'description' => [
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

            // slug
            'slug.required' => __('validation.user_status.slug.required'),
            'slug.string'   => __('validation.user_status.slug.string'),
            'slug.max'      => __('validation.user_status.slug.max'),
            'slug.unique'   => __('validation.user_status.slug.unique'),
            'slug.regex'    => __('validation.user_status.slug.regex'),

            // description
            'description.string' => __('validation.user_status.description.string'),
            'description.max'    => __('validation.user_status.description.max'),
        ];
    }



}
