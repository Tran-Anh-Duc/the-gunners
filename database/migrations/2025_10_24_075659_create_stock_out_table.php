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
        Schema::create('stock_out', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warehouse_id')->comment('Kho xuất');
            $table->unsignedBigInteger('related_order_id')->nullable()->comment('Đơn hàng liên quan');
            $table->unsignedBigInteger('created_by')->nullable()->comment('Người lập phiếu');
            $table->dateTime('date')->comment('Ngày xuất kho');
            $table->decimal('total_amount', 15, 2)->default(0)->comment('Tổng tiền xuất');
            $table->enum('status', ['draft', 'confirmed', 'cancelled'])->default('draft')->comment('Trạng thái phiếu xuất');
            $table->text('note')->nullable()->comment('Ghi chú');
            $table->timestamps();

            // Khóa ngoại
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('restrict');
            $table->foreign('related_order_id')->references('id')->on('orders')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_out');
    }
};
