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
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->comment('Đơn hàng');
            $table->unsignedBigInteger('shipper_id')->nullable()->comment('Nhân viên giao hàng');
            $table->unsignedBigInteger('vehicle_id')->nullable()->comment('Phương tiện giao hàng');
            $table->unsignedBigInteger('status_id')->default(1)->comment('Trạng thái giao hàng - FK -> statuses');
            $table->dateTime('delivery_date')->nullable()->comment('Ngày giao');
            $table->text('note')->nullable()->comment('Ghi chú');
            $table->timestamps();

            // Khóa ngoại
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('shipper_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->onDelete('set null');
            $table->foreign('status_id')->references('id')->on('statuses')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
