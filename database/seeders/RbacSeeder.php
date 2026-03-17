<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RbacSeeder extends Seeder
{
    private const DEMO_PREFIX = '[DEMO]';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now): void {
            $this->seedActions($now);
            $statusIds = $this->seedStatuses($now);
            $departmentIds = $this->seedDepartments($now);
            $userStatusIds = $this->seedUserStatuses($now);
            $roleIds = $this->seedRoles($now);
            $moduleIds = $this->seedModules($now);
            $permissionIds = $this->seedPermissions($moduleIds, $now);

            $this->seedRolePermissions($roleIds, $permissionIds);

            $userIds = $this->seedUsers($departmentIds, $userStatusIds, $now);
            $this->seedRoleUsers($userIds, $roleIds);
            $this->seedUserDepartments($userIds, $departmentIds, $now);

            $warehouseIds = $this->seedWarehouses($statusIds, $now);
            $unitIds = $this->seedUnits($now);
            $productIds = $this->seedProducts($unitIds, $statusIds, $now);
            $supplierIds = $this->seedSuppliers($now);
            $customerIds = $this->seedCustomers($now);
            $vehicleIds = $this->seedVehicles($statusIds, $now);

            $this->seedWarehouseUsers($warehouseIds, $userIds, $roleIds, $now);
            $this->purgeDemoTransactions();
            $this->seedOrdersAndFulfillment(
                $customerIds,
                $statusIds,
                $productIds,
                $userIds,
                $vehicleIds,
                $warehouseIds,
                $supplierIds,
                $now
            );
            $this->seedInventorySnapshots($warehouseIds, $productIds, $now);
        });
    }

    private function seedActions(Carbon $now): void
    {
        foreach ([
            ['key' => 'view', 'name' => 'Xem', 'description' => 'Quyền xem dữ liệu'],
            ['key' => 'create', 'name' => 'Tạo mới', 'description' => 'Quyền thêm dữ liệu'],
            ['key' => 'update', 'name' => 'Cập nhật', 'description' => 'Quyền sửa dữ liệu'],
            ['key' => 'delete', 'name' => 'Xóa', 'description' => 'Quyền xóa dữ liệu'],
            ['key' => 'export', 'name' => 'Xuất báo cáo', 'description' => 'Quyền export dữ liệu'],
            ['key' => 'import', 'name' => 'Nhập dữ liệu', 'description' => 'Quyền import dữ liệu'],
            ['key' => 'approve', 'name' => 'Phê duyệt', 'description' => 'Quyền xác nhận và phê duyệt'],
        ] as $action) {
            DB::table('actions')->updateOrInsert(
                ['key' => $action['key']],
                $this->withTimestamps($action, $now)
            );
        }
    }

    private function seedStatuses(Carbon $now): array
    {
        foreach ([
            ['name' => 'active', 'description' => 'Hoạt động'],
            ['name' => 'inactive', 'description' => 'Không hoạt động'],
            ['name' => 'pending', 'description' => 'Đang chờ xử lý'],
            ['name' => 'processing', 'description' => 'Đang xử lý'],
            ['name' => 'completed', 'description' => 'Hoàn tất'],
            ['name' => 'delivered', 'description' => 'Đã giao hàng'],
            ['name' => 'cancelled', 'description' => 'Đã hủy'],
        ] as $status) {
            DB::table('statuses')->updateOrInsert(
                ['name' => $status['name']],
                $this->withTimestamps($status, $now)
            );
        }

        return DB::table('statuses')->pluck('id', 'name')->all();
    }

    private function seedDepartments(Carbon $now): array
    {
        foreach ([
            ['name' => 'Phòng Kinh Doanh', 'description' => 'Quản lý khách hàng, đơn hàng và doanh thu'],
            ['name' => 'Phòng Vận Hành Kho', 'description' => 'Điều phối nhập xuất và tồn kho'],
            ['name' => 'Phòng Kỹ Thuật', 'description' => 'Phát triển và bảo trì hệ thống'],
            ['name' => 'Phòng Nhân Sự', 'description' => 'Tuyển dụng và vận hành nhân sự'],
            ['name' => 'Phòng Kế Toán', 'description' => 'Theo dõi công nợ và thanh toán'],
        ] as $department) {
            DB::table('departments')->updateOrInsert(
                ['name' => $department['name']],
                $this->withTimestamps($department, $now, ['deleted_at' => null])
            );
        }

        return DB::table('departments')->pluck('id', 'name')->all();
    }

    private function seedUserStatuses(Carbon $now): array
    {
        foreach ([
            ['slug' => 'active', 'name' => 'Hoạt động', 'description' => 'Người dùng đang hoạt động'],
            ['slug' => 'probation', 'name' => 'Thử việc', 'description' => 'Người dùng đang trong thời gian thử việc'],
            ['slug' => 'suspended', 'name' => 'Tạm khóa', 'description' => 'Người dùng bị tạm khóa'],
            ['slug' => 'on_leave', 'name' => 'Nghỉ phép', 'description' => 'Người dùng đang nghỉ phép'],
            ['slug' => 'resigned', 'name' => 'Đã nghỉ việc', 'description' => 'Người dùng đã nghỉ việc'],
        ] as $status) {
            DB::table('users_status')->updateOrInsert(
                ['slug' => $status['slug']],
                $this->withTimestamps($status, $now, ['deleted_at' => null])
            );
        }

        return DB::table('users_status')->pluck('id', 'slug')->all();
    }

    private function seedRoles(Carbon $now): array
    {
        $roles = [
            ['name' => 'admin', 'title' => 'Quản trị viên', 'code' => 'ROLE_ADMIN'],
            ['name' => 'manager', 'title' => 'Quản lý', 'code' => 'ROLE_MANAGER'],
            ['name' => 'warehouse', 'title' => 'Điều phối kho', 'code' => 'ROLE_WAREHOUSE'],
            ['name' => 'accountant', 'title' => 'Kế toán', 'code' => 'ROLE_ACCOUNTANT'],
            ['name' => 'staff', 'title' => 'Nhân viên', 'code' => 'ROLE_STAFF'],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['name' => $role['name']],
                $this->withTimestamps($role, $now, ['deleted_at' => null])
            );
        }

        return DB::table('roles')
            ->whereIn('name', array_column($roles, 'name'))
            ->pluck('id', 'name')
            ->all();
    }

    private function seedModules(Carbon $now): array
    {
        $modules = [
            ['name' => 'users', 'title' => 'Người dùng', 'icon' => 'users', 'order' => 1, 'code' => 'MOD_USERS'],
            ['name' => 'roles', 'title' => 'Vai trò', 'icon' => 'shield', 'order' => 2, 'code' => 'MOD_ROLES'],
            ['name' => 'departments', 'title' => 'Phòng ban', 'icon' => 'building', 'order' => 3, 'code' => 'MOD_DEPARTMENTS'],
            ['name' => 'warehouse', 'title' => 'Kho hàng', 'icon' => 'archive', 'order' => 4, 'code' => 'MOD_WAREHOUSE'],
            ['name' => 'inventory', 'title' => 'Tồn kho', 'icon' => 'boxes', 'order' => 5, 'code' => 'MOD_INVENTORY'],
            ['name' => 'products', 'title' => 'Sản phẩm', 'icon' => 'package', 'order' => 6, 'code' => 'MOD_PRODUCTS'],
            ['name' => 'orders', 'title' => 'Đơn hàng', 'icon' => 'file-text', 'order' => 7, 'code' => 'MOD_ORDERS'],
            ['name' => 'customers', 'title' => 'Khách hàng', 'icon' => 'contact', 'order' => 8, 'code' => 'MOD_CUSTOMERS'],
            ['name' => 'suppliers', 'title' => 'Nhà cung cấp', 'icon' => 'truck', 'order' => 9, 'code' => 'MOD_SUPPLIERS'],
            ['name' => 'vehicles', 'title' => 'Phương tiện', 'icon' => 'truck-fast', 'order' => 10, 'code' => 'MOD_VEHICLES'],
            ['name' => 'reports', 'title' => 'Báo cáo', 'icon' => 'bar-chart', 'order' => 11, 'code' => 'MOD_REPORTS'],
        ];

        foreach ($modules as $module) {
            DB::table('modules')->updateOrInsert(
                ['name' => $module['name']],
                $this->withTimestamps($module, $now, ['deleted_at' => null])
            );
        }

        return DB::table('modules')->pluck('id', 'name')->all();
    }

    private function seedPermissions(array $moduleIds, Carbon $now): array
    {
        $moduleActions = [
            'users' => ['view', 'create', 'update', 'delete', 'approve'],
            'roles' => ['view', 'create', 'update', 'delete'],
            'departments' => ['view', 'create', 'update'],
            'warehouse' => ['view', 'create', 'update', 'export'],
            'inventory' => ['view', 'create', 'update', 'import', 'export', 'approve'],
            'products' => ['view', 'create', 'update', 'delete', 'import', 'export'],
            'orders' => ['view', 'create', 'update', 'approve', 'export'],
            'customers' => ['view', 'create', 'update', 'export'],
            'suppliers' => ['view', 'create', 'update', 'export'],
            'vehicles' => ['view', 'create', 'update'],
            'reports' => ['view', 'export'],
        ];

        $actionTitles = [
            'view' => 'Xem',
            'create' => 'Tạo',
            'update' => 'Cập nhật',
            'delete' => 'Xóa',
            'export' => 'Xuất',
            'import' => 'Nhập',
            'approve' => 'Phê duyệt',
        ];

        foreach ($moduleActions as $moduleName => $actions) {
            foreach ($actions as $actionKey) {
                $name = "{$moduleName}.{$actionKey}";

                DB::table('permissions')->updateOrInsert(
                    ['name' => $name],
                    $this->withTimestamps([
                        'module_id' => $moduleIds[$moduleName],
                        'action_id' => $actionKey,
                        'name' => $name,
                        'title' => "{$actionTitles[$actionKey]} {$this->moduleTitle($moduleName)}",
                    ], $now)
                );
            }
        }

        return DB::table('permissions')->pluck('id', 'name')->all();
    }

    private function seedRolePermissions(array $roleIds, array $permissionIds): void
    {
        DB::table('permission_role')->whereIn('role_id', array_values($roleIds))->delete();

        $assignments = [
            'admin' => array_keys($permissionIds),
            'manager' => $this->filterPermissions($permissionIds, [
                'users', 'departments', 'warehouse', 'inventory', 'products', 'orders', 'customers', 'suppliers', 'reports', 'vehicles',
            ], ['view', 'create', 'update', 'export', 'approve']),
            'warehouse' => $this->filterPermissions($permissionIds, [
                'warehouse', 'inventory', 'products', 'orders', 'vehicles',
            ], ['view', 'create', 'update', 'import', 'export', 'approve']),
            'accountant' => $this->filterPermissions($permissionIds, [
                'orders', 'customers', 'suppliers', 'reports',
            ], ['view', 'export', 'approve']),
            'staff' => $this->filterPermissions($permissionIds, [
                'orders', 'customers', 'products',
            ], ['view', 'create', 'update']),
        ];

        foreach ($assignments as $roleName => $permissionNames) {
            foreach ($permissionNames as $permissionName) {
                DB::table('permission_role')->updateOrInsert(
                    [
                        'permission_id' => $permissionIds[$permissionName],
                        'role_id' => $roleIds[$roleName],
                    ],
                    []
                );
            }
        }
    }

    private function seedUsers(array $departmentIds, array $userStatusIds, Carbon $now): array
    {
        $users = [
            [
                'email' => 'demo.admin@the-gunners.local',
                'name' => 'Admin Demo',
                'phone' => '0909000101',
                'role' => 'admin',
                'department_id' => $departmentIds['Phòng Kinh Doanh'],
                'status_id' => $userStatusIds['active'],
            ],
            [
                'email' => 'sales.manager@the-gunners.local',
                'name' => 'Nguyễn Minh Quân',
                'phone' => '0909000102',
                'role' => 'manager',
                'department_id' => $departmentIds['Phòng Kinh Doanh'],
                'status_id' => $userStatusIds['active'],
            ],
            [
                'email' => 'warehouse.lead@the-gunners.local',
                'name' => 'Trần Hải Nam',
                'phone' => '0909000103',
                'role' => 'warehouse',
                'department_id' => $departmentIds['Phòng Vận Hành Kho'],
                'status_id' => $userStatusIds['active'],
            ],
            [
                'email' => 'warehouse.staff@the-gunners.local',
                'name' => 'Lê Phương Anh',
                'phone' => '0909000104',
                'role' => 'staff',
                'department_id' => $departmentIds['Phòng Vận Hành Kho'],
                'status_id' => $userStatusIds['active'],
            ],
            [
                'email' => 'accountant@the-gunners.local',
                'name' => 'Phạm Thu Trang',
                'phone' => '0909000105',
                'role' => 'accountant',
                'department_id' => $departmentIds['Phòng Kế Toán'],
                'status_id' => $userStatusIds['active'],
            ],
            [
                'email' => 'sales.staff@the-gunners.local',
                'name' => 'Đỗ Gia Hưng',
                'phone' => '0909000106',
                'role' => 'staff',
                'department_id' => $departmentIds['Phòng Kinh Doanh'],
                'status_id' => $userStatusIds['probation'],
            ],
            [
                'email' => 'tech.staff@the-gunners.local',
                'name' => 'Bùi Khánh Linh',
                'phone' => '0909000107',
                'role' => 'staff',
                'department_id' => $departmentIds['Phòng Kỹ Thuật'],
                'status_id' => $userStatusIds['active'],
            ],
            [
                'email' => 'hr.staff@the-gunners.local',
                'name' => 'Hoàng Ngọc Mai',
                'phone' => '0909000108',
                'role' => 'staff',
                'department_id' => $departmentIds['Phòng Nhân Sự'],
                'status_id' => $userStatusIds['on_leave'],
            ],
        ];

        foreach ($users as $index => $user) {
            DB::table('users')->updateOrInsert(
                ['email' => $user['email']],
                $this->withTimestamps([
                    'department_id' => $user['department_id'],
                    'status_id' => $user['status_id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'password' => Hash::make('password'),
                    'phone' => $user['phone'],
                    'avatar' => null,
                    'role' => $user['role'],
                    'is_active' => true,
                    'last_login_at' => $now->copy()->subDays($index + 1),
                    'change_password_at' => $now->copy()->subDays($index + 10),
                    'remember_token' => null,
                ], $now)
            );
        }

        return DB::table('users')
            ->whereIn('email', array_column($users, 'email'))
            ->pluck('id', 'email')
            ->all();
    }

    private function seedRoleUsers(array $userIds, array $roleIds): void
    {
        $emails = array_keys($userIds);
        DB::table('role_user')->whereIn('user_id', array_values($userIds))->delete();

        $mapping = [
            'demo.admin@the-gunners.local' => 'admin',
            'sales.manager@the-gunners.local' => 'manager',
            'warehouse.lead@the-gunners.local' => 'warehouse',
            'warehouse.staff@the-gunners.local' => 'staff',
            'accountant@the-gunners.local' => 'accountant',
            'sales.staff@the-gunners.local' => 'staff',
            'tech.staff@the-gunners.local' => 'staff',
            'hr.staff@the-gunners.local' => 'staff',
        ];

        foreach ($mapping as $email => $roleName) {
            if (! in_array($email, $emails, true)) {
                continue;
            }

            DB::table('role_user')->insert([
                'role_id' => $roleIds[$roleName],
                'user_id' => $userIds[$email],
            ]);
        }
    }

    private function seedUserDepartments(array $userIds, array $departmentIds, Carbon $now): void
    {
        DB::table('user_department')->whereIn('user_id', array_values($userIds))->delete();

        $assignments = [
            ['email' => 'demo.admin@the-gunners.local', 'department' => 'Phòng Kinh Doanh', 'position' => 'Giám đốc vận hành', 'is_main' => true],
            ['email' => 'sales.manager@the-gunners.local', 'department' => 'Phòng Kinh Doanh', 'position' => 'Trưởng phòng kinh doanh', 'is_main' => true],
            ['email' => 'warehouse.lead@the-gunners.local', 'department' => 'Phòng Vận Hành Kho', 'position' => 'Điều phối kho', 'is_main' => true],
            ['email' => 'warehouse.staff@the-gunners.local', 'department' => 'Phòng Vận Hành Kho', 'position' => 'Nhân viên kho', 'is_main' => true],
            ['email' => 'accountant@the-gunners.local', 'department' => 'Phòng Kế Toán', 'position' => 'Kế toán tổng hợp', 'is_main' => true],
            ['email' => 'sales.staff@the-gunners.local', 'department' => 'Phòng Kinh Doanh', 'position' => 'Nhân viên kinh doanh', 'is_main' => true],
            ['email' => 'tech.staff@the-gunners.local', 'department' => 'Phòng Kỹ Thuật', 'position' => 'Lập trình viên backend', 'is_main' => true],
            ['email' => 'hr.staff@the-gunners.local', 'department' => 'Phòng Nhân Sự', 'position' => 'Chuyên viên nhân sự', 'is_main' => true],
            ['email' => 'demo.admin@the-gunners.local', 'department' => 'Phòng Vận Hành Kho', 'position' => 'Phụ trách dự án kho', 'is_main' => false],
        ];

        foreach ($assignments as $assignment) {
            DB::table('user_department')->insert([
                'user_id' => $userIds[$assignment['email']],
                'department_id' => $departmentIds[$assignment['department']],
                'assigned_at' => $now,
                'is_main' => $assignment['is_main'],
                'position' => $assignment['position'],
                'ended_at' => null,
            ]);
        }
    }

    private function seedWarehouses(array $statusIds, Carbon $now): array
    {
        foreach ([
            ['name' => 'Kho Hà Nội', 'address' => 'Số 1 Hoàng Quốc Việt, Hà Nội'],
            ['name' => 'Kho TP.HCM', 'address' => '100 Nguyễn Huệ, TP.HCM'],
            ['name' => 'Kho Đà Nẵng', 'address' => '12 Bạch Đằng, Đà Nẵng'],
        ] as $warehouse) {
            DB::table('warehouses')->updateOrInsert(
                ['name' => $warehouse['name']],
                $this->withTimestamps([
                    'name' => $warehouse['name'],
                    'address' => $warehouse['address'],
                    'status_id' => $statusIds['active'],
                ], $now)
            );
        }

        return DB::table('warehouses')->pluck('id', 'name')->all();
    }

    private function seedUnits(Carbon $now): array
    {
        foreach ([
            ['name' => 'Cái', 'code' => 'pcs'],
            ['name' => 'Hộp', 'code' => 'box'],
            ['name' => 'Kg', 'code' => 'kg'],
            ['name' => 'Chai', 'code' => 'bottle'],
            ['name' => 'Thùng', 'code' => 'carton'],
        ] as $unit) {
            DB::table('units')->updateOrInsert(
                ['code' => $unit['code']],
                $this->withTimestamps($unit, $now)
            );
        }

        return DB::table('units')->pluck('id', 'code')->all();
    }

    private function seedProducts(array $unitIds, array $statusIds, Carbon $now): array
    {
        $products = [
            ['code' => 'DEMO-SP001', 'name' => 'Bánh quy sữa', 'price' => 25000, 'unit' => 'box', 'description' => 'Bánh quy đóng hộp bán chạy'],
            ['code' => 'DEMO-SP002', 'name' => 'Nước suối 500ml', 'price' => 5000, 'unit' => 'bottle', 'description' => 'Nước đóng chai 500ml'],
            ['code' => 'DEMO-SP003', 'name' => 'Gạo ST25', 'price' => 18000, 'unit' => 'kg', 'description' => 'Gạo thơm cao cấp'],
            ['code' => 'DEMO-SP004', 'name' => 'Cà phê rang xay', 'price' => 135000, 'unit' => 'box', 'description' => 'Cà phê hộp 500g'],
            ['code' => 'DEMO-SP005', 'name' => 'Nước rửa chén', 'price' => 32000, 'unit' => 'bottle', 'description' => 'Dung dịch 750ml'],
            ['code' => 'DEMO-SP006', 'name' => 'Khăn giấy hộp', 'price' => 42000, 'unit' => 'box', 'description' => 'Khăn giấy 2 lớp'],
            ['code' => 'DEMO-SP007', 'name' => 'Mì ăn liền thùng', 'price' => 118000, 'unit' => 'carton', 'description' => 'Thùng 30 gói'],
            ['code' => 'DEMO-SP008', 'name' => 'Đường tinh luyện', 'price' => 24000, 'unit' => 'kg', 'description' => 'Đường trắng đóng túi'],
        ];

        foreach ($products as $product) {
            DB::table('products')->updateOrInsert(
                ['code' => $product['code']],
                $this->withTimestamps([
                    'code' => $product['code'],
                    'name' => $product['name'],
                    'price' => $product['price'],
                    'description' => $product['description'],
                    'unit_id' => $unitIds[$product['unit']],
                    'status_id' => $statusIds['active'],
                ], $now)
            );
        }

        return DB::table('products')->pluck('id', 'code')->all();
    }

    private function seedSuppliers(Carbon $now): array
    {
        $suppliers = [
            ['email' => 'supply.hn@demo.local', 'name' => 'Công ty Phân Phối Miền Bắc', 'contact_name' => 'Nguyễn Công Lý', 'phone' => '0901112201', 'address' => 'Hà Nội'],
            ['email' => 'supply.hcm@demo.local', 'name' => 'Công ty Nguồn Hàng Sài Gòn', 'contact_name' => 'Trần Bảo Yến', 'phone' => '0901112202', 'address' => 'TP.HCM'],
            ['email' => 'supply.dn@demo.local', 'name' => 'Nhà cung cấp Miền Trung', 'contact_name' => 'Lê Huy Bảo', 'phone' => '0901112203', 'address' => 'Đà Nẵng'],
            ['email' => 'supply.fmcg@demo.local', 'name' => 'Đối tác FMCG Toàn Quốc', 'contact_name' => 'Phạm Thu Hiền', 'phone' => '0901112204', 'address' => 'Bình Dương'],
        ];

        foreach ($suppliers as $supplier) {
            DB::table('suppliers')->updateOrInsert(
                ['email' => $supplier['email']],
                $this->withTimestamps([
                    'name' => $supplier['name'],
                    'contact_name' => $supplier['contact_name'],
                    'phone' => $supplier['phone'],
                    'email' => $supplier['email'],
                    'address' => $supplier['address'],
                ], $now)
            );
        }

        return DB::table('suppliers')->pluck('id', 'email')->all();
    }

    private function seedCustomers(Carbon $now): array
    {
        $customers = [
            ['email' => 'customer.alpha@demo.local', 'name' => 'Cửa hàng Alpha', 'phone' => '0988888801', 'address' => 'Cầu Giấy, Hà Nội'],
            ['email' => 'customer.beta@demo.local', 'name' => 'Siêu thị Beta', 'phone' => '0988888802', 'address' => 'Quận 1, TP.HCM'],
            ['email' => 'customer.gamma@demo.local', 'name' => 'Tạp hóa Gamma', 'phone' => '0988888803', 'address' => 'Hải Châu, Đà Nẵng'],
            ['email' => 'customer.delta@demo.local', 'name' => 'Đại lý Delta', 'phone' => '0988888804', 'address' => 'Thủ Đức, TP.HCM'],
            ['email' => 'customer.epsilon@demo.local', 'name' => 'Cửa hàng Epsilon', 'phone' => '0988888805', 'address' => 'Long Biên, Hà Nội'],
            ['email' => 'customer.zeta@demo.local', 'name' => 'Minimart Zeta', 'phone' => '0988888806', 'address' => 'Liên Chiểu, Đà Nẵng'],
        ];

        foreach ($customers as $customer) {
            DB::table('customers')->updateOrInsert(
                ['email' => $customer['email']],
                $this->withTimestamps([
                    'name' => $customer['name'],
                    'phone' => $customer['phone'],
                    'email' => $customer['email'],
                    'address' => $customer['address'],
                ], $now)
            );
        }

        return DB::table('customers')->pluck('id', 'email')->all();
    }

    private function seedVehicles(array $statusIds, Carbon $now): array
    {
        foreach ([
            ['plate_number' => '29A-DEMO01', 'type' => 'Xe tải nhẹ', 'capacity' => 1.5],
            ['plate_number' => '51D-DEMO02', 'type' => 'Xe tải 2 tấn', 'capacity' => 2.0],
            ['plate_number' => '43C-DEMO03', 'type' => 'Xe van', 'capacity' => 0.8],
        ] as $vehicle) {
            DB::table('vehicles')->updateOrInsert(
                ['plate_number' => $vehicle['plate_number']],
                $this->withTimestamps([
                    'plate_number' => $vehicle['plate_number'],
                    'type' => $vehicle['type'],
                    'capacity' => $vehicle['capacity'],
                    'status_id' => $statusIds['active'],
                ], $now)
            );
        }

        return DB::table('vehicles')->pluck('id', 'plate_number')->all();
    }

    private function seedWarehouseUsers(array $warehouseIds, array $userIds, array $roleIds, Carbon $now): void
    {
        $managedUserIds = [
            $userIds['demo.admin@the-gunners.local'],
            $userIds['sales.manager@the-gunners.local'],
            $userIds['warehouse.lead@the-gunners.local'],
            $userIds['warehouse.staff@the-gunners.local'],
        ];

        DB::table('warehouse_user')->whereIn('user_id', $managedUserIds)->delete();

        $assignments = [
            ['warehouse' => 'Kho Hà Nội', 'email' => 'warehouse.lead@the-gunners.local', 'role' => 'warehouse'],
            ['warehouse' => 'Kho Hà Nội', 'email' => 'warehouse.staff@the-gunners.local', 'role' => 'staff'],
            ['warehouse' => 'Kho TP.HCM', 'email' => 'sales.manager@the-gunners.local', 'role' => 'manager'],
            ['warehouse' => 'Kho Đà Nẵng', 'email' => 'demo.admin@the-gunners.local', 'role' => 'admin'],
        ];

        foreach ($assignments as $assignment) {
            DB::table('warehouse_user')->insert([
                'warehouse_id' => $warehouseIds[$assignment['warehouse']],
                'user_id' => $userIds[$assignment['email']],
                'role_id' => $roleIds[$assignment['role']],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function purgeDemoTransactions(): void
    {
        $demoOrderIds = DB::table('orders')
            ->where('note', 'like', self::DEMO_PREFIX.'%')
            ->pluck('id');

        if ($demoOrderIds->isNotEmpty()) {
            $demoStockOutIds = DB::table('stock_out')
                ->whereIn('related_order_id', $demoOrderIds)
                ->pluck('id');

            if ($demoStockOutIds->isNotEmpty()) {
                DB::table('stock_out_items')->whereIn('stock_out_id', $demoStockOutIds)->delete();
                DB::table('stock_out')->whereIn('id', $demoStockOutIds)->delete();
            }

            DB::table('shipments')->whereIn('order_id', $demoOrderIds)->delete();
            DB::table('payments')->whereIn('order_id', $demoOrderIds)->delete();
            DB::table('order_items')->whereIn('order_id', $demoOrderIds)->delete();
            DB::table('orders')->whereIn('id', $demoOrderIds)->delete();
        }

        $demoStockInIds = DB::table('stock_in')
            ->where('note', 'like', self::DEMO_PREFIX.'%')
            ->pluck('id');

        if ($demoStockInIds->isNotEmpty()) {
            DB::table('stock_in_items')->whereIn('stock_in_id', $demoStockInIds)->delete();
            DB::table('stock_in')->whereIn('id', $demoStockInIds)->delete();
        }

        $extraDemoStockOutIds = DB::table('stock_out')
            ->where('note', 'like', self::DEMO_PREFIX.'%')
            ->pluck('id');

        if ($extraDemoStockOutIds->isNotEmpty()) {
            DB::table('stock_out_items')->whereIn('stock_out_id', $extraDemoStockOutIds)->delete();
            DB::table('stock_out')->whereIn('id', $extraDemoStockOutIds)->delete();
        }
    }

    private function seedOrdersAndFulfillment(
        array $customerIds,
        array $statusIds,
        array $productIds,
        array $userIds,
        array $vehicleIds,
        array $warehouseIds,
        array $supplierIds,
        Carbon $now
    ): void {
        $orderDefinitions = [
            [
                'customer' => 'customer.alpha@demo.local',
                'status' => 'pending',
                'date' => $now->copy()->subDays(5),
                'note' => self::DEMO_PREFIX.' Don ban le Ha Noi',
                'items' => [
                    ['code' => 'DEMO-SP001', 'quantity' => 4, 'price' => 25000],
                    ['code' => 'DEMO-SP002', 'quantity' => 12, 'price' => 5000],
                ],
                'payment_method' => 'cash',
                'shipper' => null,
                'vehicle' => null,
            ],
            [
                'customer' => 'customer.beta@demo.local',
                'status' => 'delivered',
                'date' => $now->copy()->subDays(4),
                'note' => self::DEMO_PREFIX.' Don giao TP HCM',
                'items' => [
                    ['code' => 'DEMO-SP004', 'quantity' => 2, 'price' => 135000],
                    ['code' => 'DEMO-SP006', 'quantity' => 3, 'price' => 42000],
                ],
                'payment_method' => 'bank',
                'shipper' => 'warehouse.staff@the-gunners.local',
                'vehicle' => '51D-DEMO02',
            ],
            [
                'customer' => 'customer.gamma@demo.local',
                'status' => 'completed',
                'date' => $now->copy()->subDays(3),
                'note' => self::DEMO_PREFIX.' Don dai ly Da Nang',
                'items' => [
                    ['code' => 'DEMO-SP003', 'quantity' => 20, 'price' => 18000],
                    ['code' => 'DEMO-SP008', 'quantity' => 10, 'price' => 24000],
                ],
                'payment_method' => 'cod',
                'shipper' => 'warehouse.lead@the-gunners.local',
                'vehicle' => '43C-DEMO03',
            ],
            [
                'customer' => 'customer.delta@demo.local',
                'status' => 'processing',
                'date' => $now->copy()->subDays(2),
                'note' => self::DEMO_PREFIX.' Don sieu thi Thu Duc',
                'items' => [
                    ['code' => 'DEMO-SP005', 'quantity' => 8, 'price' => 32000],
                    ['code' => 'DEMO-SP007', 'quantity' => 3, 'price' => 118000],
                ],
                'payment_method' => 'credit_card',
                'shipper' => null,
                'vehicle' => null,
            ],
            [
                'customer' => 'customer.epsilon@demo.local',
                'status' => 'cancelled',
                'date' => $now->copy()->subDay(),
                'note' => self::DEMO_PREFIX.' Don huy can doi dia chi',
                'items' => [
                    ['code' => 'DEMO-SP002', 'quantity' => 24, 'price' => 5000],
                    ['code' => 'DEMO-SP006', 'quantity' => 2, 'price' => 42000],
                ],
                'payment_method' => null,
                'shipper' => null,
                'vehicle' => null,
            ],
        ];

        foreach ($orderDefinitions as $orderDefinition) {
            $lineItems = collect($orderDefinition['items'])->map(function (array $item) use ($productIds): array {
                return [
                    'product_id' => $productIds[$item['code']],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $item['quantity'] * $item['price'],
                ];
            });

            $orderId = DB::table('orders')->insertGetId([
                'customer_id' => $customerIds[$orderDefinition['customer']],
                'status_id' => $statusIds[$orderDefinition['status']],
                'order_date' => $orderDefinition['date'],
                'total_amount' => $lineItems->sum('subtotal'),
                'note' => $orderDefinition['note'],
                'created_at' => $orderDefinition['date'],
                'updated_at' => $orderDefinition['date'],
            ]);

            foreach ($lineItems as $lineItem) {
                DB::table('order_items')->insert([
                    'order_id' => $orderId,
                    'product_id' => $lineItem['product_id'],
                    'quantity' => $lineItem['quantity'],
                    'price' => $lineItem['price'],
                    'subtotal' => $lineItem['subtotal'],
                    'created_at' => $orderDefinition['date'],
                    'updated_at' => $orderDefinition['date'],
                ]);
            }

            if ($orderDefinition['payment_method']) {
                DB::table('payments')->insert([
                    'order_id' => $orderId,
                    'status_id' => $statusIds[$orderDefinition['status'] === 'cancelled' ? 'cancelled' : 'completed'],
                    'payment_method' => $orderDefinition['payment_method'],
                    'amount' => $lineItems->sum('subtotal'),
                    'paid_date' => $orderDefinition['date']->copy()->addHours(2),
                    'created_at' => $orderDefinition['date'],
                    'updated_at' => $orderDefinition['date'],
                ]);
            }

            if ($orderDefinition['shipper'] && $orderDefinition['vehicle']) {
                DB::table('shipments')->insert([
                    'order_id' => $orderId,
                    'shipper_id' => $userIds[$orderDefinition['shipper']],
                    'vehicle_id' => $vehicleIds[$orderDefinition['vehicle']],
                    'status_id' => $statusIds[$orderDefinition['status']],
                    'delivery_date' => $orderDefinition['date']->copy()->addDay(),
                    'note' => self::DEMO_PREFIX.' Giao tu kho den khach hang',
                    'created_at' => $orderDefinition['date'],
                    'updated_at' => $orderDefinition['date'],
                ]);
            }
        }

        $this->seedStockFlows($supplierIds, $warehouseIds, $userIds, $productIds, $now);
    }

    private function seedStockFlows(
        array $supplierIds,
        array $warehouseIds,
        array $userIds,
        array $productIds,
        Carbon $now
    ): void {
        $stockInDefinitions = [
            [
                'warehouse' => 'Kho Hà Nội',
                'supplier' => 'supply.hn@demo.local',
                'creator' => 'warehouse.lead@the-gunners.local',
                'date' => $now->copy()->subDays(6),
                'note' => self::DEMO_PREFIX.' Nhap hang banh quy va nuoc',
                'items' => [
                    ['code' => 'DEMO-SP001', 'quantity' => 40, 'price' => 19000],
                    ['code' => 'DEMO-SP002', 'quantity' => 120, 'price' => 3800],
                ],
            ],
            [
                'warehouse' => 'Kho TP.HCM',
                'supplier' => 'supply.hcm@demo.local',
                'creator' => 'warehouse.lead@the-gunners.local',
                'date' => $now->copy()->subDays(4),
                'note' => self::DEMO_PREFIX.' Nhap hang hoa gia dung',
                'items' => [
                    ['code' => 'DEMO-SP005', 'quantity' => 60, 'price' => 25000],
                    ['code' => 'DEMO-SP006', 'quantity' => 30, 'price' => 35000],
                ],
            ],
            [
                'warehouse' => 'Kho Đà Nẵng',
                'supplier' => 'supply.dn@demo.local',
                'creator' => 'demo.admin@the-gunners.local',
                'date' => $now->copy()->subDays(3),
                'note' => self::DEMO_PREFIX.' Nhap hang kho mien Trung',
                'items' => [
                    ['code' => 'DEMO-SP003', 'quantity' => 150, 'price' => 15000],
                    ['code' => 'DEMO-SP008', 'quantity' => 80, 'price' => 19000],
                ],
            ],
        ];

        foreach ($stockInDefinitions as $definition) {
            $items = collect($definition['items'])->map(function (array $item) use ($productIds): array {
                return [
                    'product_id' => $productIds[$item['code']],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $item['quantity'] * $item['price'],
                ];
            });

            $stockInId = DB::table('stock_in')->insertGetId([
                'warehouse_id' => $warehouseIds[$definition['warehouse']],
                'supplier_id' => $supplierIds[$definition['supplier']],
                'created_by' => $userIds[$definition['creator']],
                'date' => $definition['date'],
                'total_amount' => $items->sum('subtotal'),
                'status' => 'confirmed',
                'note' => $definition['note'],
                'created_at' => $definition['date'],
                'updated_at' => $definition['date'],
            ]);

            foreach ($items as $item) {
                DB::table('stock_in_items')->insert([
                    'stock_in_id' => $stockInId,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $item['subtotal'],
                    'created_at' => $definition['date'],
                    'updated_at' => $definition['date'],
                ]);
            }
        }

        $demoOrders = DB::table('orders')
            ->where('note', 'like', self::DEMO_PREFIX.'%')
            ->orderBy('id')
            ->get(['id', 'status_id', 'total_amount']);

        $stockOutDefinitions = [
            [
                'warehouse' => 'Kho Hà Nội',
                'order_index' => 0,
                'creator' => 'sales.manager@the-gunners.local',
                'date' => $now->copy()->subDays(5)->addHours(3),
                'note' => self::DEMO_PREFIX.' Xuat kho Ha Noi cho don alpha',
                'items' => [
                    ['code' => 'DEMO-SP001', 'quantity' => 4, 'price' => 25000],
                    ['code' => 'DEMO-SP002', 'quantity' => 12, 'price' => 5000],
                ],
            ],
            [
                'warehouse' => 'Kho TP.HCM',
                'order_index' => 1,
                'creator' => 'warehouse.lead@the-gunners.local',
                'date' => $now->copy()->subDays(4)->addHours(5),
                'note' => self::DEMO_PREFIX.' Xuat kho HCM giao sieu thi',
                'items' => [
                    ['code' => 'DEMO-SP004', 'quantity' => 2, 'price' => 135000],
                    ['code' => 'DEMO-SP006', 'quantity' => 3, 'price' => 42000],
                ],
            ],
            [
                'warehouse' => 'Kho Đà Nẵng',
                'order_index' => 2,
                'creator' => 'demo.admin@the-gunners.local',
                'date' => $now->copy()->subDays(3)->addHours(4),
                'note' => self::DEMO_PREFIX.' Xuat kho Da Nang cho dai ly',
                'items' => [
                    ['code' => 'DEMO-SP003', 'quantity' => 20, 'price' => 18000],
                    ['code' => 'DEMO-SP008', 'quantity' => 10, 'price' => 24000],
                ],
            ],
        ];

        foreach ($stockOutDefinitions as $definition) {
            $items = collect($definition['items'])->map(function (array $item) use ($productIds): array {
                return [
                    'product_id' => $productIds[$item['code']],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $item['quantity'] * $item['price'],
                ];
            });

            $relatedOrder = $demoOrders[$definition['order_index']] ?? null;

            $stockOutId = DB::table('stock_out')->insertGetId([
                'warehouse_id' => $warehouseIds[$definition['warehouse']],
                'related_order_id' => $relatedOrder?->id,
                'created_by' => $userIds[$definition['creator']],
                'date' => $definition['date'],
                'total_amount' => $items->sum('subtotal'),
                'status' => 'confirmed',
                'note' => $definition['note'],
                'created_at' => $definition['date'],
                'updated_at' => $definition['date'],
            ]);

            foreach ($items as $item) {
                DB::table('stock_out_items')->insert([
                    'stock_out_id' => $stockOutId,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $item['subtotal'],
                    'created_at' => $definition['date'],
                    'updated_at' => $definition['date'],
                ]);
            }
        }
    }

    private function seedInventorySnapshots(array $warehouseIds, array $productIds, Carbon $now): void
    {
        $today = $now->toDateString();
        $period = $now->copy()->startOfMonth()->toDateString();

        $snapshots = [
            ['warehouse' => 'Kho Hà Nội', 'product' => 'DEMO-SP001', 'opening' => 80, 'import' => 40, 'export' => 12, 'average_cost' => 19000],
            ['warehouse' => 'Kho Hà Nội', 'product' => 'DEMO-SP002', 'opening' => 200, 'import' => 120, 'export' => 12, 'average_cost' => 3800],
            ['warehouse' => 'Kho TP.HCM', 'product' => 'DEMO-SP004', 'opening' => 20, 'import' => 0, 'export' => 2, 'average_cost' => 100000],
            ['warehouse' => 'Kho TP.HCM', 'product' => 'DEMO-SP006', 'opening' => 40, 'import' => 30, 'export' => 5, 'average_cost' => 35000],
            ['warehouse' => 'Kho Đà Nẵng', 'product' => 'DEMO-SP003', 'opening' => 200, 'import' => 150, 'export' => 20, 'average_cost' => 15000],
            ['warehouse' => 'Kho Đà Nẵng', 'product' => 'DEMO-SP008', 'opening' => 120, 'import' => 80, 'export' => 10, 'average_cost' => 19000],
        ];

        foreach ($snapshots as $snapshot) {
            $closingQuantity = $snapshot['opening'] + $snapshot['import'] - $snapshot['export'];
            $importValue = $snapshot['import'] * $snapshot['average_cost'];
            $exportValue = $snapshot['export'] * $snapshot['average_cost'];
            $closingValue = $closingQuantity * $snapshot['average_cost'];

            DB::table('opening_inventories')->updateOrInsert(
                [
                    'warehouse_id' => $warehouseIds[$snapshot['warehouse']],
                    'product_id' => $productIds[$snapshot['product']],
                    'period' => $period,
                ],
                $this->withTimestamps([
                    'opening_quantity' => $snapshot['opening'],
                    'opening_unit_price' => $snapshot['average_cost'],
                    'opening_total_value' => $snapshot['opening'] * $snapshot['average_cost'],
                    'period' => $period,
                ], $now)
            );

            DB::table('inventories')->updateOrInsert(
                [
                    'warehouse_id' => $warehouseIds[$snapshot['warehouse']],
                    'product_id' => $productIds[$snapshot['product']],
                    'inventory_date' => $today,
                ],
                $this->withTimestamps([
                    'opening_quantity' => $snapshot['opening'],
                    'import_quantity' => $snapshot['import'],
                    'export_quantity' => $snapshot['export'],
                    'closing_quantity' => $closingQuantity,
                    'import_value' => $importValue,
                    'export_value' => $exportValue,
                    'closing_value' => $closingValue,
                    'average_cost' => $snapshot['average_cost'],
                ], $now)
            );
        }
    }

    private function moduleTitle(string $moduleName): string
    {
        return [
            'users' => 'người dùng',
            'roles' => 'vai trò',
            'departments' => 'phòng ban',
            'warehouse' => 'kho hàng',
            'inventory' => 'tồn kho',
            'products' => 'sản phẩm',
            'orders' => 'đơn hàng',
            'customers' => 'khách hàng',
            'suppliers' => 'nhà cung cấp',
            'vehicles' => 'phương tiện',
            'reports' => 'báo cáo',
        ][$moduleName];
    }

    private function filterPermissions(array $permissionIds, array $modules, array $actions): array
    {
        return collect($permissionIds)
            ->keys()
            ->filter(function (string $permissionName) use ($modules, $actions): bool {
                [$module, $action] = explode('.', $permissionName, 2);

                return in_array($module, $modules, true) && in_array($action, $actions, true);
            })
            ->values()
            ->all();
    }

    private function withTimestamps(array $attributes, Carbon $now, array $extra = []): array
    {
        return array_merge($attributes, [
            'created_at' => $now,
            'updated_at' => $now,
        ], $extra);
    }
}
