<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateActionRequest extends BaseFormRequest
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
    public function rules()
    {

        $data = [
            'key'         => ['required', 'string', 'max:50', 'alpha_dash'],
            'name'        => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:255'],
        ];
        return  $data;
    }

    public function messages()
    {

        $data =  [
            'key.required' => __('validation.required', ['attribute' => 'key']),
            'name.required' => __('validation.required', ['attribute' => 'name']),
            'description.required' => __('validation.required', ['attribute' => 'description']),
        ];
        return $data;
    }


}
