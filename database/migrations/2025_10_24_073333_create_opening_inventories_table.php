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
        Schema::create('opening_inventories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warehouse_id')->comment('Kho');
            $table->unsignedBigInteger('product_id')->comment('Sản phẩm');
            $table->decimal('opening_quantity', 15, 2)->default(0)->comment('Số lượng tồn đầu kỳ');
            $table->decimal('opening_unit_price', 15, 2)->default(0)->comment('Giá đơn vị tồn đầu kỳ');
            $table->decimal('opening_total_value', 20, 2)->default(0)->comment('Thành tiền tồn đầu kỳ');
            $table->date('period')->comment('Kỳ kế toán - YYYY-MM-01');
            $table->timestamps();

            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opening_inventories');
    }
};
