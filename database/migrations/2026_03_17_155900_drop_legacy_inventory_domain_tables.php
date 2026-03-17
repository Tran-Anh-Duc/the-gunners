<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
        // This migration is a one-way reset from the legacy schema.
    }
};
