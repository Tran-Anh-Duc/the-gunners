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
        Schema::create('stock_in', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warehouse_id')->comment('Kho nhập');
            $table->unsignedBigInteger('supplier_id')->nullable()->comment('Nhà cung cấp');
            $table->unsignedBigInteger('created_by')->nullable()->comment('Người tạo phiếu');
            $table->dateTime('date')->comment('Ngày nhập kho');
            $table->decimal('total_amount', 15, 2)->default(0)->comment('Tổng tiền nhập');
            $table->enum('status', ['draft', 'confirmed', 'cancelled'])->default('draft')->comment('Trạng thái phiếu nhập');
            $table->text('note')->nullable()->comment('Ghi chú');
            $table->timestamps();

            // Khóa ngoại
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('restrict');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_in');
    }
};
