<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tạo nhóm bảng bán hàng.
     *
     * Thiết kế chính:
     * - `orders` giữ phần header;
     * - `order_items` giữ snapshot chi tiết từng dòng hàng.
     *
     * Cách tách này giúp dữ liệu lịch sử không bị đổi theo catalog mới.
     */
    public function up(): void
    {
        // `orders` là chứng từ bán hàng, tách header và item để dễ mở rộng.
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('order_no', 50);
            $table->dateTime('order_date');
            $table->string('status', 30)->default('draft');
            $table->string('payment_status', 30)->default('unpaid');
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('shipping_amount', 18, 2)->default(0);
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->decimal('paid_amount', 18, 2)->default(0);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'order_no']);
            $table->index(['business_id', 'order_date']);
            $table->index(['business_id', 'status']);
            $table->index(['business_id', 'customer_id']);
            $table->index(['business_id', 'warehouse_id']);
        });

        // `order_items` snapshot tên và SKU sản phẩm để không bị ảnh hưởng bởi việc sửa catalog về sau.
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->string('product_sku', 100);
            $table->string('product_name');
            $table->decimal('quantity', 18, 3)->default(0);
            $table->decimal('unit_price', 18, 2)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('line_total', 18, 2)->default(0);
            $table->timestamps();

            $table->index(['business_id', 'order_id']);
            $table->index(['business_id', 'product_id']);
        });
    }

    public function down(): void
    {
        // Xóa item trước rồi mới xóa header.
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
