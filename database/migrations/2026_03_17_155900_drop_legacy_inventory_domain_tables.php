<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Xóa toàn bộ schema domain cũ để chuẩn bị dựng schema MVP mới.
     *
     * Đây là migration "dọn nền" một chiều,
     * phục vụ quá trình tái cấu trúc lại domain inventory của dự án.
     */
    public function up(): void
    {
        // Reset toàn bộ schema domain cũ để dựng lại MVP mới từ đầu.
        Schema::disableForeignKeyConstraints();

        foreach ([
            'shipments',
            'warehouse_user',
            'user_department',
            'permission_user',
            'permission_role',
            'role_user',
            'stock_out_items',
            'stock_in_items',
            'order_items',
            'payments',
            'stock_out',
            'stock_in',
            'inventories',
            'opening_inventories',
            'orders',
            'products',
            'suppliers',
            'customers',
            'vehicles',
            'warehouses',
            'departments',
            'users_status',
            'permissions',
            'modules',
            'actions',
            'roles',
            'statuses',
            'units',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Migration này là bước reset một chiều nên không hỗ trợ rollback ngược.
    }
};
