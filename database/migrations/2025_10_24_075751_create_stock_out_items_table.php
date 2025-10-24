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
        Schema::create('stock_out_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_out_id')->comment('Phiếu xuất kho');
            $table->unsignedBigInteger('product_id')->comment('Sản phẩm');
            $table->decimal('quantity', 15, 2)->default(0)->comment('Số lượng xuất');
            $table->decimal('price', 15, 2)->default(0)->comment('Giá xuất');
            $table->decimal('subtotal', 15, 2)->default(0)->comment('Thành tiền (quantity * price)');
            $table->timestamps();

            // Khóa ngoại
            $table->foreign('stock_out_id')->references('id')->on('stock_out')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_out_items');
    }
};
