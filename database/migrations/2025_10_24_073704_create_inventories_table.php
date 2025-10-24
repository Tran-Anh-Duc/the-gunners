<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warehouse_id')->comment('Kho');
            $table->unsignedBigInteger('product_id')->comment('Sản phẩm');
            $table->date('inventory_date')->comment('Ngày thống kê');
            $table->decimal('opening_quantity', 15, 2)->default(0)->comment('Tồn đầu ngày');
            $table->decimal('import_quantity', 15, 2)->default(0)->comment('Tổng nhập');
            $table->decimal('export_quantity', 15, 2)->default(0)->comment('Tổng xuất');
            $table->decimal('closing_quantity', 15, 2)->default(0)->comment('Tồn cuối ngày');

            $table->decimal('import_value', 20, 2)->default(0)->comment('Tổng tiền nhập');
            $table->decimal('export_value', 20, 2)->default(0)->comment('Tổng tiền xuất');
            $table->decimal('closing_value', 20, 2)->default(0)->comment('Tổng giá trị tồn cuối');

            $table->decimal('average_cost', 15, 4)->default(0)->comment('Giá trung bình');
            $table->timestamps();

            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');

            $table->unique(['warehouse_id', 'product_id', 'inventory_date'], 'uniq_inventory_per_day');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
