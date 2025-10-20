<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RbacSeeder extends Seeder
{
    public function run(): void
    {
        // Modules
        DB::table('modules')->insert([
            ['id' => 1, 'name' => 'user_management', 'title' => 'Quản lý người dùng', 'icon' => 'users', 'order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'role_management', 'title' => 'Quản lý vai trò', 'icon' => 'shield', 'order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'permission_management', 'title' => 'Quản lý quyền', 'icon' => 'key', 'order' => 3, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Actions
        DB::table('actions')->insert([
            ['id' => 1, 'key' => 'view', 'name' => 'Xem', 'description' => 'Quyền xem dữ liệu', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'key' => 'create', 'name' => 'Tạo mới', 'description' => 'Quyền tạo dữ liệu', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'key' => 'edit', 'name' => 'Chỉnh sửa', 'description' => 'Quyền chỉnh sửa dữ liệu', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'key' => 'delete', 'name' => 'Xóa', 'description' => 'Quyền xóa dữ liệu', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Permissions
        DB::table('permissions')->insert([
            ['module_id' => 1, 'action_id' => 'view', 'name' => 'user.view', 'title' => 'Xem danh sách người dùng', 'created_at' => now(), 'updated_at' => now()],
            ['module_id' => 1, 'action_id' => 'create', 'name' => 'user.create', 'title' => 'Tạo người dùng mới', 'created_at' => now(), 'updated_at' => now()],
            ['module_id' => 1, 'action_id' => 'edit', 'name' => 'user.edit', 'title' => 'Chỉnh sửa người dùng', 'created_at' => now(), 'updated_at' => now()],
            ['module_id' => 1, 'action_id' => 'delete', 'name' => 'user.delete', 'title' => 'Xóa người dùng', 'created_at' => now(), 'updated_at' => now()],
            ['module_id' => 2, 'action_id' => 'view', 'name' => 'role.view', 'title' => 'Xem vai trò', 'created_at' => now(), 'updated_at' => now()],
            ['module_id' => 3, 'action_id' => 'view', 'name' => 'permission.view', 'title' => 'Xem quyền hạn', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Roles
        DB::table('roles')->insert([
            ['id' => 1, 'name' => 'admin', 'title' => 'Quản trị hệ thống', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'manager', 'title' => 'Quản lý', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'user', 'title' => 'Người dùng thường', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Users
        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Admin User', 'email' => 'admin@example.com', 'password' => Hash::make('123456'), 'role' => 'admin', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Manager', 'email' => 'manager@example.com', 'password' => Hash::make('123456'), 'role' => 'manager', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'Normal User', 'email' => 'user@example.com', 'password' => Hash::make('123456'), 'role' => 'user', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Role - User
        DB::table('role_user')->insert([
            ['role_id' => 1, 'user_id' => 1],
            ['role_id' => 2, 'user_id' => 2],
            ['role_id' => 3, 'user_id' => 3],
        ]);
    }
}
