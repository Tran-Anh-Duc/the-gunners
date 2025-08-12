<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Module;

class PermissionRollbackSeeder extends Seeder
{
    public function run()
    {
        // Xóa hết permission và module đã seed
        Permission::truncate();
        Module::truncate();
    }
}
