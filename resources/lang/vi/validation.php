<?php

return [
    'required' => 'Trường :attribute là bắt buộc.',
    'string'   => 'Trường :attribute phải là chuỗi.',
    'max'      => [
        'string' => 'Trường :attribute không được dài quá :max ký tự.',
    ],
    'alpha_dash' => 'Trường :attribute chỉ được chứa chữ cái, số, dấu gạch ngang và gạch dưới.',
    'user' => [
        'name' => [
            'required' => 'Vui lòng nhập tên người dùng.',
            'max' => 'Tên người dùng không được vượt quá :max ký tự.',
        ],
        'email' => [
            'required' => 'Vui lòng nhập email.',
            'email' => 'Email không đúng định dạng.',
            'unique' => 'Email đã tồn tại trong hệ thống.',
        ],
        'password' => [
            'required' => 'Vui lòng nhập mật khẩu.',
            'min' => 'Mật khẩu phải có ít nhất :min ký tự.',
            'confirmed' => 'Mật khẩu xác nhận không khớp.',
        ],
    ],
    'user_status' => [
        'name' => [
            'required' => 'Tên trạng thái người dùng là bắt buộc.',
            'string'   => 'Tên trạng thái người dùng phải là chuỗi.',
            'max'      => 'Tên trạng thái người dùng không được vượt quá :max ký tự.',
        ],
        'slug' => [
            'required' => 'Slug là bắt buộc.',
            'string'   => 'Slug phải là chuỗi.',
            'max'      => 'Slug không được vượt quá :max ký tự.',
            'unique'   => 'Slug đã tồn tại.',
            'regex'    => 'Slug chỉ được chứa chữ thường, số và dấu gạch dưới (_).',
        ],
        'description' => [
            'string' => 'Mô tả phải là chuỗi.',
            'max'    => 'Mô tả không được vượt quá :max ký tự.',
        ],
    ],

];
