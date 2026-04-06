<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\NameSlug;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
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
                'name_slug' => NameSlug::from('admin'),
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
