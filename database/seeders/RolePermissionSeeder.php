<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Module;
use App\Models\Action;
use Illuminate\Support\Facades\Hash;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        // 1. Tạo Actions
        $actions = [
            ['name' => 'View', 'key' => 'view', 'description' => 'Xem dữ liệu'],
            ['name' => 'Add', 'key' => 'add', 'description' => 'Thêm dữ liệu'],
            ['name' => 'Edit', 'key' => 'edit', 'description' => 'Sửa dữ liệu'],
            ['name' => 'Delete', 'key' => 'delete', 'description' => 'Xóa dữ liệu'],
            ['name' => 'Export Excel', 'key' => 'export_excel', 'description' => 'Xuất Excel'],
            ['name' => 'Print', 'key' => 'print', 'description' => 'In dữ liệu'],
        ];

        foreach ($actions as $act) {
            Action::create($act);
        }

        // 2. Tạo Modules
        $modules = [
            'user_management',
            'post_management',
            'report_management'
        ];

        foreach ($modules as $moduleName) {
            Module::create(['name' => $moduleName]);
        }

        // 3. Tạo Permissions (kết hợp module + action)
        foreach ($modules as $moduleName) {
            $module = Module::where('name', $moduleName)->first();

            foreach (Action::all() as $action) {
                Permission::create([
                    'name'      => "{$moduleName}_{$action->key}",
                    'module_id' => $module->id,
                    'action_id' => $action->id
                ]);
            }
        }

        // 4. Tạo Roles
        $roles = ['admin', 'manager', 'user'];
        foreach ($roles as $roleName) {
            Role::create(['name' => $roleName]);
        }

        // 5. Gán quyền cho admin
        $adminRole = Role::where('name', 'admin')->first();
        $adminRole->permissions()->sync(Permission::all()->pluck('id')->toArray());

        // 6. Gán quyền cho manager (ví dụ: chỉ được post_management + view report_management)
        $managerRole = Role::where('name', 'manager')->first();
        $managerPermissions = Permission::where('name', 'like', 'post_management_%')
            ->orWhere('name', 'report_management_view')
            ->pluck('id')->toArray();
        $managerRole->permissions()->sync($managerPermissions);

        // 7. Tạo Users
        $users = [
            ['name' => 'Admin User', 'email' => 'admin@gmail.com', 'role' => 'admin'],
            ['name' => 'Manager User', 'email' => 'manager@gmail.com', 'role' => 'manager'],
            ['name' => 'Normal User', 'email' => 'user@gmail.com', 'role' => 'user'],
        ];

        foreach ($users as $data) {
            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => Hash::make('123456'),
                'is_active'=> true
            ]);

            $role = Role::where('name', $data['role'])->first();
            $user->roles()->attach($role->id);
        }

        // 8. Gán quyền đặc biệt cho User thường
        $normalUser = User::where('email', 'user@gmail.com')->first();
        $extraPermission = Permission::where('name', 'user_management_view')->first();
        if ($extraPermission) {
            $normalUser->permissions()->attach($extraPermission->id);
        }
    }
}

