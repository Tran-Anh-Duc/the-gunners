<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // ví dụ một số module
        $modules = [
            ['name'=>'user_management','title'=>'User Management'],
            ['name'=>'post_management','title'=>'Post Management'],
            ['name'=>'report','title'=>'Report'],
        ];

        foreach ($modules as $m) {
            Module::updateOrCreate(['name'=>$m['name']], $m);
        }

        // actions mặc định
        $actions = ['view','add','edit','delete','export'];

        // tạo permission cho mỗi module + action
        foreach (Module::all() as $module) {
            foreach ($actions as $action) {
                $name = "{$action}_{$module->name}";
                Permission::updateOrCreate(
                    ['name'=>$name],
                    ['module_id'=>$module->id, 'action'=>$action, 'title'=>ucfirst($action).' '.$module->title]
                );
            }
        }

        // tạo role admin
        $adminRole = Role::firstOrCreate(['name'=>'admin'], ['title'=>'Administrator']);

        // gán tất cả permissions cho admin
        $adminRole->permissions()->sync(Permission::pluck('id')->toArray());

        // tạo user admin
        $admin = User::firstOrCreate(
            ['email'=>'admin@gmail.com'],
            [
                'name'=>'admin1',
                'password'=>Hash::make('123456'),
                'role'=>'admin',
                'is_active'=>true,
                'phone' => null,
                'avatar' => null,
                'last_login_at' => null,
            ]
        );

        // gán role admin cho user (pivot)
        $admin->roles()->syncWithoutDetaching([$adminRole->id]);
    }
}
