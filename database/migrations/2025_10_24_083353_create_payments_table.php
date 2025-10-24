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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->comment('Đơn hàng');
            $table->unsignedBigInteger('status_id')->default(1)->comment('Trạng thái thanh toán - FK -> statuses');
            $table->enum('payment_method', ['cash', 'bank', 'cod', 'credit_card'])
                ->default('cash')->comment('Hình thức thanh toán');
            $table->decimal('amount', 15, 2)->default(0)->comment('Số tiền thanh toán');
            $table->dateTime('paid_date')->nullable()->comment('Ngày thanh toán');
            $table->timestamps();

            // Khóa ngoại
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('status_id')->references('id')->on('statuses')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
