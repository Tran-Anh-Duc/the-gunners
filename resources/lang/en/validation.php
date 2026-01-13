<?php

return [
    'required' => 'The :attribute field is required.',
    'string'   => 'The :attribute must be a string.',
    'max'      => [
        'string' => 'The :attribute may not be greater than :max characters.',
    ],
    'alpha_dash' => 'The :attribute may only contain letters, numbers, dashes, and underscores.',
    'user' => [
        'name' => [
            'required' => 'Please enter the user name.',
            'max' => 'The user name may not be greater than :max characters.',
        ],
        'email' => [
            'required' => 'Please enter the email address.',
            'email' => 'The email address format is invalid.',
            'unique' => 'The email address has already been taken.',
        ],
        'password' => [
            'required' => 'Please enter the password.',
            'min' => 'The password must be at least :min characters.',
            'confirmed' => 'The password confirmation does not match.',
        ],
    ],
    'user_status' => [
        'name' => [
            'required' => 'User status name is required.',
            'string'   => 'User status name must be a string.',
            'max'      => 'User status name may not be greater than :max characters.',
        ],
        'slug' => [
            'required' => 'Slug is required.',
            'string'   => 'Slug must be a string.',
            'max'      => 'Slug may not be greater than :max characters.',
            'unique'   => 'Slug has already been taken.',
            'regex'    => 'Slug may only contain lowercase letters, numbers, and underscores (_).',
        ],
        'description' => [
            'string' => 'Description must be a string.',
            'max'    => 'Description may not be greater than :max characters.',
        ],
    ],

];
