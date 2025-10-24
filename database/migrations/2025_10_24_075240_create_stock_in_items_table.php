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
        Schema::create('stock_in_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_in_id')->comment('Phiếu nhập kho');
            $table->unsignedBigInteger('product_id')->comment('Sản phẩm');
            $table->decimal('quantity', 15, 2)->default(0)->comment('Số lượng nhập');
            $table->decimal('price', 15, 2)->default(0)->comment('Giá nhập');
            $table->decimal('subtotal', 15, 2)->default(0)->comment('Thành tiền (quantity * price)');
            $table->timestamps();

            // Khóa ngoại
            $table->foreign('stock_in_id')->references('id')->on('stock_in')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_in_items');
    }
};
