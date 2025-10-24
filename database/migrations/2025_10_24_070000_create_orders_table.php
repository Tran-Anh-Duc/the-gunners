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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->comment('Khách hàng');
            $table->unsignedBigInteger('status_id')->default(1)->comment('Trạng thái đơn hàng - FK từ bảng statuses');
            $table->dateTime('order_date')->comment('Ngày đặt hàng');
            $table->decimal('total_amount', 15, 2)->default(0)->comment('Tổng tiền đơn hàng');
            $table->text('note')->nullable()->comment('Ghi chú');
            $table->timestamps();

            // Khóa ngoại
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('status_id')->references('id')->on('statuses')->onDelete('restrict');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
