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

];
