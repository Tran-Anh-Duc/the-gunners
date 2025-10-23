<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RbacSeeder extends Seeder
{
    public function run(): void
    {
        // ======== DEPARTMENTS ========
        DB::table('departments')->insert([
            ['name' => 'Phòng Kinh Doanh', 'description' => 'Phụ trách bán hàng & marketing', 'created_at' => now()],
            ['name' => 'Phòng Kỹ Thuật', 'description' => 'Phát triển và bảo trì hệ thống', 'created_at' => now()],
            ['name' => 'Phòng Nhân Sự', 'description' => 'Quản lý nhân viên và tuyển dụng', 'created_at' => now()],
        ]);

        // ======== USER STATUS ========
        DB::table('users_status')->insert([
            ['name' => 'Hoạt động', 'description' => 'Người dùng đang hoạt động', 'created_at' => now()],
            ['name' => 'Tạm khóa', 'description' => 'Người dùng bị tạm khóa', 'created_at' => now()],
            ['name' => 'Đã nghỉ việc', 'description' => 'Người dùng đã ngừng làm việc', 'created_at' => now()],
        ]);

        // ======== USERS ========
        DB::table('users')->insert([
            [
                'department_id' => 1,
                'status_id' => 1,
                'name' => 'Nguyễn Văn A',
                'email' => 'a@example.com',
                'password' => Hash::make('password'),
                'phone' => '0909000001',
                'role' => 'admin',
                'created_at' => now()
            ],
            [
                'department_id' => 2,
                'status_id' => 1,
                'name' => 'Trần Thị B',
                'email' => 'b@example.com',
                'password' => Hash::make('password'),
                'phone' => '0909000002',
                'role' => 'user',
                'created_at' => now()
            ],
            [
                'department_id' => 3,
                'status_id' => 1,
                'name' => 'Lê Văn C',
                'email' => 'c@example.com',
                'password' => Hash::make('password'),
                'phone' => '0909000003',
                'role' => 'user',
                'created_at' => now()
            ],
        ]);

        // ======== ROLES ========
        DB::table('roles')->insert([
            ['name' => 'admin', 'title' => 'Quản trị viên hệ thống', 'code' => 'ROLE_ADMIN', 'created_at' => now()],
            ['name' => 'manager', 'title' => 'Trưởng phòng', 'code' => 'ROLE_MANAGER', 'created_at' => now()],
            ['name' => 'staff', 'title' => 'Nhân viên', 'code' => 'ROLE_STAFF', 'created_at' => now()],
        ]);

        // ======== ROLE_USER ========
        DB::table('role_user')->insert([
            ['role_id' => 1, 'user_id' => 1],
            ['role_id' => 2, 'user_id' => 2],
            ['role_id' => 3, 'user_id' => 3],
        ]);

        // ======== MODULES ========
        DB::table('modules')->insert([
            ['name' => 'users', 'title' => 'Quản lý người dùng', 'icon' => 'user', 'order' => 1, 'code' => 'MOD_USERS', 'created_at' => now()],
            ['name' => 'departments', 'title' => 'Phòng ban', 'icon' => 'building', 'order' => 2, 'code' => 'MOD_DEPT', 'created_at' => now()],
            ['name' => 'reports', 'title' => 'Báo cáo', 'icon' => 'file-text', 'order' => 3, 'code' => 'MOD_REPORT', 'created_at' => now()],
        ]);

        // ======== PERMISSIONS ========
        DB::table('permissions')->insert([
            ['module_id' => 1, 'action_id' => 'view', 'name' => 'users.view', 'title' => 'Xem người dùng', 'created_at' => now()],
            ['module_id' => 1, 'action_id' => 'create', 'name' => 'users.create', 'title' => 'Tạo người dùng', 'created_at' => now()],
            ['module_id' => 1, 'action_id' => 'update', 'name' => 'users.update', 'title' => 'Cập nhật người dùng', 'created_at' => now()],
            ['module_id' => 1, 'action_id' => 'delete', 'name' => 'users.delete', 'title' => 'Xóa người dùng', 'created_at' => now()],
            ['module_id' => 2, 'action_id' => 'view', 'name' => 'departments.view', 'title' => 'Xem phòng ban', 'created_at' => now()],
            ['module_id' => 3, 'action_id' => 'export', 'name' => 'reports.export', 'title' => 'Xuất báo cáo', 'created_at' => now()],
        ]);

        // ======== PERMISSION_ROLE ========
        DB::table('permission_role')->insert([
            ['permission_id' => 1, 'role_id' => 1],
            ['permission_id' => 2, 'role_id' => 1],
            ['permission_id' => 3, 'role_id' => 1],
            ['permission_id' => 4, 'role_id' => 1],
            ['permission_id' => 5, 'role_id' => 1],
            ['permission_id' => 6, 'role_id' => 1],
            ['permission_id' => 5, 'role_id' => 2],
            ['permission_id' => 6, 'role_id' => 3],
        ]);

        // ======== USER_DEPARTMENT ========
        DB::table('user_department')->insert([
            ['user_id' => 1, 'department_id' => 1, 'is_main' => '1', 'position' => 'Giám đốc', 'assigned_at' => now()],
            ['user_id' => 2, 'department_id' => 2, 'is_main' => '1', 'position' => 'Trưởng phòng kỹ thuật', 'assigned_at' => now()],
            ['user_id' => 3, 'department_id' => 3, 'is_main' => '1', 'position' => 'Nhân viên', 'assigned_at' => now()],
        ]);
    }
}
