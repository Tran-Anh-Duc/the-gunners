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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->comment('Đơn hàng');
            $table->unsignedBigInteger('product_id')->comment('Sản phẩm');
            $table->decimal('quantity', 15, 2)->default(0)->comment('Số lượng đặt');
            $table->decimal('price', 15, 2)->default(0)->comment('Giá bán');
            $table->decimal('subtotal', 15, 2)->default(0)->comment('Thành tiền (quantity * price)');
            $table->timestamps();

            // Khóa ngoại
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
