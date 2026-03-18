<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tạo nhóm bảng nhập kho, xuất kho và kiểm kho.
     *
     * Các bảng này mô hình hóa toàn bộ chứng từ tác động trực tiếp tới tồn:
     * - `stock_in` và `stock_in_items`;
     * - `stock_out` và `stock_out_items`;
     * - `stock_adjustments` và `stock_adjustment_items`.
     */
    public function up(): void
    {
        // `stock_in` ghi nhận nhập kho từ nhà cung cấp hoặc các nguồn nhập khác.
        Schema::create('stock_in', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('stock_in_no', 50);
            $table->string('reference_no', 100)->nullable();
            $table->string('stock_in_type', 30)->default('purchase');
            $table->dateTime('stock_in_date');
            $table->string('status', 30)->default('draft');
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'stock_in_no']);
            $table->index(['business_id', 'stock_in_date']);
            $table->index(['business_id', 'status']);
            $table->index(['business_id', 'supplier_id']);
        });

        Schema::create('stock_in_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('stock_in_id')->constrained('stock_in')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->string('product_sku', 100);
            $table->string('product_name');
            $table->decimal('quantity', 18, 3)->default(0);
            $table->decimal('unit_cost', 18, 2)->default(0);
            $table->decimal('line_total', 18, 2)->default(0);
            $table->timestamps();

            $table->index(['business_id', 'stock_in_id']);
            $table->index(['business_id', 'product_id']);
        });

        // `stock_out` là chứng từ xuất kho, có thể gắn với đơn hàng.
        Schema::create('stock_out', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('stock_out_no', 50);
            $table->string('reference_no', 100)->nullable();
            $table->string('stock_out_type', 30)->default('sale');
            $table->dateTime('stock_out_date');
            $table->string('status', 30)->default('draft');
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'stock_out_no']);
            $table->index(['business_id', 'stock_out_date']);
            $table->index(['business_id', 'status']);
            $table->index(['business_id', 'order_id']);
        });

        Schema::create('stock_out_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('stock_out_id')->constrained('stock_out')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->string('product_sku', 100);
            $table->string('product_name');
            $table->decimal('quantity', 18, 3)->default(0);
            $table->decimal('unit_price', 18, 2)->default(0);
            $table->decimal('line_total', 18, 2)->default(0);
            $table->timestamps();

            $table->index(['business_id', 'stock_out_id']);
            $table->index(['business_id', 'product_id']);
        });

        // `stock_adjustments` dùng cho kiểm kho hoặc lệch kho, rất cần thiết ngay cả với MVP.
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('adjustment_no', 50);
            $table->dateTime('adjustment_date');
            $table->string('reason')->nullable();
            $table->string('status', 30)->default('draft');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'adjustment_no']);
            $table->index(['business_id', 'adjustment_date']);
            $table->index(['business_id', 'status']);
        });

        // Lưu cả expected và counted để sau này audit được chênh lệch tồn kho.
        Schema::create('stock_adjustment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('stock_adjustment_id')->constrained('stock_adjustments')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->string('product_sku', 100);
            $table->string('product_name');
            $table->decimal('expected_qty', 18, 3)->default(0);
            $table->decimal('counted_qty', 18, 3)->default(0);
            $table->decimal('difference_qty', 18, 3)->default(0);
            $table->decimal('unit_cost', 18, 2)->default(0);
            $table->decimal('line_total', 18, 2)->default(0);
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'stock_adjustment_id']);
            $table->index(['business_id', 'product_id']);
        });
    }

    public function down(): void
    {
        // Xóa các bảng chi tiết trước rồi mới xóa bảng header.
        Schema::dropIfExists('stock_adjustment_items');
        Schema::dropIfExists('stock_adjustments');
        Schema::dropIfExists('stock_out_items');
        Schema::dropIfExists('stock_out');
        Schema::dropIfExists('stock_in_items');
        Schema::dropIfExists('stock_in');
    }
};
