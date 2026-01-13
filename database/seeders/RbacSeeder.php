<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class RbacSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $now = Carbon::now();

        /*--------------------------------------------------------------
         | 1. ACTIONS (CRUD)
         --------------------------------------------------------------*/
        DB::table('actions')->truncate();
        DB::table('actions')->insert([
            ['key' => 'view', 'name' => 'Xem', 'description' => 'Quyền xem dữ liệu', 'created_at' => $now],
            ['key' => 'create', 'name' => 'Tạo mới', 'description' => 'Quyền thêm dữ liệu', 'created_at' => $now],
            ['key' => 'update', 'name' => 'Cập nhật', 'description' => 'Quyền sửa dữ liệu', 'created_at' => $now],
            ['key' => 'delete', 'name' => 'Xóa', 'description' => 'Quyền xóa dữ liệu', 'created_at' => $now],
            ['key' => 'export', 'name' => 'Xuất báo cáo', 'description' => 'Quyền export', 'created_at' => $now],
        ]);

        /*--------------------------------------------------------------
         | 2. STATUSES
         --------------------------------------------------------------*/
        DB::table('statuses')->truncate();
        DB::table('statuses')->insert([
            ['name' => 'active', 'description' => 'Hoạt động', 'created_at' => $now],
            ['name' => 'inactive', 'description' => 'Không hoạt động', 'created_at' => $now],
            ['name' => 'pending', 'description' => 'Đang xử lý', 'created_at' => $now],
            ['name' => 'delivered', 'description' => 'Đã giao hàng', 'created_at' => $now],
        ]);

        /*--------------------------------------------------------------
         | 3. DEPARTMENTS
         --------------------------------------------------------------*/
        DB::table('departments')->truncate();
        DB::table('departments')->insert([
            ['name' => 'Phòng Kinh Doanh', 'description' => 'Phụ trách bán hàng & marketing', 'created_at' => $now],
            ['name' => 'Phòng Kỹ Thuật', 'description' => 'Phát triển và bảo trì hệ thống', 'created_at' => $now],
            ['name' => 'Phòng Nhân Sự', 'description' => 'Quản lý nhân viên và tuyển dụng', 'created_at' => $now],
        ]);

        /*--------------------------------------------------------------
         | 4. USERS_STATUS
         --------------------------------------------------------------*/
        DB::table('users_status')->truncate();
        DB::table('users_status')->insert([
            [
                'name'        => 'Hoạt động',
                'slug'        => 'active',
                'description' => 'Người dùng đang hoạt động',
                'created_at'  => $now,
            ],
            [
                'name'        => 'Tạm khóa',
                'slug'        => 'suspended',
                'description' => 'Người dùng bị tạm khóa',
                'created_at'  => $now,
            ],
            [
                'name'        => 'Đã nghỉ việc',
                'slug'        => 'resigned',
                'description' => 'Người dùng đã nghỉ việc',
                'created_at'  => $now,
            ],
        ]);

        /*--------------------------------------------------------------
         | 5. USERS
         --------------------------------------------------------------*/
        DB::table('users')->truncate();
        DB::table('users')->insert([
            [
                'department_id' => 1,
                'status_id' => 1,
                'name' => 'Nguyễn Văn A',
                'email' => 'a@example.com',
                'password' => Hash::make('password'),
                'phone' => '0909000001',
                'role' => 'admin',
                'is_active' => 1,
                'created_at' => $now
            ],
            [
                'department_id' => 2,
                'status_id' => 1,
                'name' => 'Trần Thị B',
                'email' => 'b@example.com',
                'password' => Hash::make('password'),
                'phone' => '0909000002',
                'role' => 'manager',
                'is_active' => 1,
                'created_at' => $now
            ],
            [
                'department_id' => 3,
                'status_id' => 1,
                'name' => 'Lê Văn C',
                'email' => 'c@example.com',
                'password' => Hash::make('password'),
                'phone' => '0909000003',
                'role' => 'staff',
                'is_active' => 1,
                'created_at' => $now
            ],
        ]);

        /*--------------------------------------------------------------
         | 6. ROLES & PIVOTS
         --------------------------------------------------------------*/
        DB::table('roles')->truncate();
        DB::table('roles')->insert([
            ['name' => 'admin', 'title' => 'Quản trị viên', 'code' => 'ROLE_ADMIN', 'created_at' => $now],
            ['name' => 'manager', 'title' => 'Trưởng phòng', 'code' => 'ROLE_MANAGER', 'created_at' => $now],
            ['name' => 'staff', 'title' => 'Nhân viên', 'code' => 'ROLE_STAFF', 'created_at' => $now],
        ]);

        DB::table('role_user')->truncate();
        DB::table('role_user')->insert([
            ['role_id' => 1, 'user_id' => 1],
            ['role_id' => 2, 'user_id' => 2],
            ['role_id' => 3, 'user_id' => 3],
        ]);

        /*--------------------------------------------------------------
         | 7. MODULES & PERMISSIONS
         --------------------------------------------------------------*/
        DB::table('modules')->truncate();
        DB::table('modules')->insert([
            ['name' => 'users', 'title' => 'Người dùng', 'icon' => 'user', 'order' => 1, 'code' => 'MOD_USERS', 'created_at' => $now],
            ['name' => 'warehouse', 'title' => 'Kho hàng', 'icon' => 'archive', 'order' => 2, 'code' => 'MOD_WAREHOUSE', 'created_at' => $now],
            ['name' => 'orders', 'title' => 'Đơn hàng', 'icon' => 'file', 'order' => 3, 'code' => 'MOD_ORDERS', 'created_at' => $now],
            ['name' => 'reports', 'title' => 'Báo cáo', 'icon' => 'bar-chart', 'order' => 4, 'code' => 'MOD_REPORTS', 'created_at' => $now],
        ]);

        DB::table('permissions')->truncate();
        DB::table('permissions')->insert([
            ['module_id' => 1, 'action_id' => 'view', 'name' => 'users.view', 'title' => 'Xem người dùng', 'created_at' => $now],
            ['module_id' => 1, 'action_id' => 'create', 'name' => 'users.create', 'title' => 'Tạo người dùng', 'created_at' => $now],
            ['module_id' => 2, 'action_id' => 'view', 'name' => 'warehouse.view', 'title' => 'Xem kho', 'created_at' => $now],
            ['module_id' => 3, 'action_id' => 'view', 'name' => 'orders.view', 'title' => 'Xem đơn hàng', 'created_at' => $now],
            ['module_id' => 4, 'action_id' => 'export', 'name' => 'reports.export', 'title' => 'Xuất báo cáo', 'created_at' => $now],
        ]);

        DB::table('permission_role')->truncate();
        DB::table('permission_role')->insert([
            ['permission_id' => 1, 'role_id' => 1],
            ['permission_id' => 2, 'role_id' => 1],
            ['permission_id' => 3, 'role_id' => 1],
            ['permission_id' => 4, 'role_id' => 1],
            ['permission_id' => 5, 'role_id' => 1],
            ['permission_id' => 3, 'role_id' => 2],
            ['permission_id' => 4, 'role_id' => 3],
        ]);

        /*--------------------------------------------------------------
         | 8. WAREHOUSES, UNITS, PRODUCTS
         --------------------------------------------------------------*/
        DB::table('warehouses')->truncate();
        DB::table('warehouses')->insert([
            ['name' => 'Kho Hà Nội', 'address' => 'Số 1 Hoàng Quốc Việt, Hà Nội', 'status_id' => 1, 'created_at' => $now],
            ['name' => 'Kho TP.HCM', 'address' => '100 Nguyễn Huệ, TP.HCM', 'status_id' => 1, 'created_at' => $now],
        ]);

        DB::table('units')->truncate();
        DB::table('units')->insert([
            ['name' => 'Cái', 'code' => 'pcs', 'created_at' => $now],
            ['name' => 'Hộp', 'code' => 'box', 'created_at' => $now],
            ['name' => 'Kg', 'code' => 'kg', 'created_at' => $now],
        ]);

        DB::table('products')->truncate();
        DB::table('products')->insert([
            ['code' => 'SP001', 'name' => 'Bánh quy sữa', 'price' => 25000, 'unit_id' => 2, 'status_id' => 1, 'created_at' => $now],
            ['code' => 'SP002', 'name' => 'Nước suối 500ml', 'price' => 5000, 'unit_id' => 1, 'status_id' => 1, 'created_at' => $now],
            ['code' => 'SP003', 'name' => 'Gạo ST25', 'price' => 18000, 'unit_id' => 3, 'status_id' => 1, 'created_at' => $now],
        ]);

        /*--------------------------------------------------------------
         | 9. SUPPLIERS, CUSTOMERS
         --------------------------------------------------------------*/
        DB::table('suppliers')->truncate();
        DB::table('suppliers')->insert([
            ['name' => 'Công ty ABC', 'contact_name' => 'Nguyễn Văn A', 'phone' => '0901112222', 'email' => 'abc@supplier.com', 'address' => 'Hà Nội', 'created_at' => $now],
            ['name' => 'Công ty XYZ', 'contact_name' => 'Trần Thị B', 'phone' => '0903334444', 'email' => 'xyz@supplier.com', 'address' => 'TP.HCM', 'created_at' => $now],
        ]);

        DB::table('customers')->truncate();
        DB::table('customers')->insert([
            ['name' => 'Nguyễn Văn Khách', 'phone' => '0988888888', 'email' => 'customer1@gmail.com', 'address' => 'Cầu Giấy, Hà Nội', 'created_at' => $now],
            ['name' => 'Trần Thị Mua', 'phone' => '0977777777', 'email' => 'customer2@gmail.com', 'address' => 'Quận 1, TP.HCM', 'created_at' => $now],
        ]);

        /*--------------------------------------------------------------
         | 10. ORDERS + ITEMS + PAYMENTS + SHIPMENTS
         --------------------------------------------------------------*/
        DB::table('orders')->truncate();
        DB::table('orders')->insert([
            ['customer_id' => 1, 'status_id' => 1, 'order_date' => $now, 'total_amount' => 55000, 'note' => 'Đơn hàng mẫu', 'created_at' => $now],
        ]);

        DB::table('order_items')->truncate();
        DB::table('order_items')->insert([
            ['order_id' => 1, 'product_id' => 1, 'quantity' => 2, 'price' => 25000, 'subtotal' => 50000, 'created_at' => $now],
            ['order_id' => 1, 'product_id' => 2, 'quantity' => 1, 'price' => 5000, 'subtotal' => 5000, 'created_at' => $now],
        ]);

        DB::table('payments')->truncate();
        DB::table('payments')->insert([
            ['order_id' => 1, 'status_id' => 1, 'payment_method' => 'cash', 'amount' => 55000, 'paid_date' => $now, 'created_at' => $now],
        ]);

        DB::table('vehicles')->truncate();
        DB::table('vehicles')->insert([
            ['plate_number' => '29A-12345', 'type' => 'Xe tải nhỏ', 'capacity' => 2.5, 'status_id' => 1, 'created_at' => $now],
        ]);

        DB::table('shipments')->truncate();
        DB::table('shipments')->insert([
            ['order_id' => 1, 'shipper_id' => 3, 'vehicle_id' => 1, 'status_id' => 1, 'delivery_date' => $now->addDay(), 'note' => 'Giao xe tải', 'created_at' => $now],
        ]);

        /*--------------------------------------------------------------
         | 11. STOCK & INVENTORY DEMO
         --------------------------------------------------------------*/
        DB::table('stock_in')->truncate();
        DB::table('stock_in')->insert([
            ['warehouse_id' => 1, 'supplier_id' => 1, 'created_by' => 1, 'date' => $now, 'total_amount' => 100000, 'status' => 'confirmed', 'note' => 'Nhập hàng đầu kỳ', 'created_at' => $now],
        ]);

        DB::table('stock_in_items')->truncate();
        DB::table('stock_in_items')->insert([
            ['stock_in_id' => 1, 'product_id' => 1, 'quantity' => 10, 'price' => 20000, 'subtotal' => 200000, 'created_at' => $now],
        ]);

        DB::table('stock_out')->truncate();
        DB::table('stock_out')->insert([
            ['warehouse_id' => 1, 'related_order_id' => 1, 'created_by' => 2, 'date' => $now, 'total_amount' => 55000, 'status' => 'confirmed', 'note' => 'Xuất hàng đơn 1', 'created_at' => $now],
        ]);

        DB::table('stock_out_items')->truncate();
        DB::table('stock_out_items')->insert([
            ['stock_out_id' => 1, 'product_id' => 1, 'quantity' => 2, 'price' => 25000, 'subtotal' => 50000, 'created_at' => $now],
        ]);

        DB::table('inventories')->truncate();
        DB::table('inventories')->insert([
            [
                'warehouse_id' => 1,
                'product_id' => 1,
                'inventory_date' => $now->toDateString(),
                'opening_quantity' => 10,
                'import_quantity' => 10,
                'export_quantity' => 2,
                'closing_quantity' => 8,
                'import_value' => 200000,
                'export_value' => 50000,
                'closing_value' => 150000,
                'average_cost' => 20000,
                'created_at' => $now,
            ]
        ]);

        DB::table('opening_inventories')->truncate();
        DB::table('opening_inventories')->insert([
            [
                'warehouse_id' => 1,
                'product_id' => 1,
                'opening_quantity' => 10,
                'opening_unit_price' => 20000,
                'opening_total_value' => 200000,
                'period' => $now->startOfMonth(),
                'created_at' => $now,
            ]
        ]);

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
