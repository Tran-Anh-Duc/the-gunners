<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Gỡ các bảng nghiệp vụ đã bị loại khỏi project.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach ([
            'current_stocks',
            'inventory_movements',
            'payments',
            'stock_adjustment_items',
            'stock_adjustments',
            'stock_out_items',
            'stock_out',
            'stock_in_items',
            'stock_in',
            'order_items',
            'orders',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Không hỗ trợ rollback vì domain này đã bị loại khỏi codebase.
    }
};
