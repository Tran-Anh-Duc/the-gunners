<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'trananhducty@gmail.com'], // kiểm tra email này đã có chưa
            [
                'name' => 'admin',
                'password' => Hash::make('123456'),
                'role' => 'admin',
                'is_active' => true,
                'phone' => null,
                'avatar' => null,
                'last_login_at' => null,
            ]
        );
    }
}
