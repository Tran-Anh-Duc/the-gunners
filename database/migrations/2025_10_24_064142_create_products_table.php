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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable()->comment('Mã sản phẩm');
            $table->string('name')->nullable()->comment('Tên sản phẩm');
            $table->decimal('price', 15, 2)->nullable()->comment('Giá sản phẩm');
            $table->text('description')->nullable()->comment('Mô tả sản phẩm');
            $table->unsignedBigInteger('unit_id')->nullable()->comment('Đơn vị tính - FK từ bảng units');
            $table->unsignedBigInteger('status_id')->default(1)->comment('Trạng thái - FK từ bảng statuses');
            $table->timestamps();

            // Khóa ngoại
            $table->foreign('unit_id')
                ->references('id')
                ->on('units')
                ->onUpdate('cascade')
                ->onDelete('set null');

            $table->foreign('status_id')
                ->references('id')
                ->on('statuses')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
