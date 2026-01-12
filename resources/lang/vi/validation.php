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
];
