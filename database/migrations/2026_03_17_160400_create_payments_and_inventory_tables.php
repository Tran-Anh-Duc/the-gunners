<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tạo nhóm bảng thanh toán và tồn kho.
     *
     * Đây là nơi tách rõ:
     * - `payments`: chứng từ thu/chi;
     * - `inventory_movements`: nguồn sự thật của tồn kho;
     * - `current_stocks`: bảng tổng hợp để truy vấn nhanh.
     */
    public function up(): void
    {
        // `payments` giữ mức cơ bản cho thu hoặc chi liên quan đến order và nhập hàng.
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('stock_in_id')->nullable()->constrained('stock_in')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('payment_no', 50);
            $table->string('direction', 10)->default('in');
            $table->string('method', 30)->default('cash');
            $table->string('status', 30)->default('paid');
            $table->decimal('amount', 18, 2)->default(0);
            $table->dateTime('payment_date');
            $table->string('reference_no', 100)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'payment_no']);
            $table->index(['business_id', 'payment_date']);
            $table->index(['business_id', 'status']);
            $table->index(['business_id', 'order_id']);
        });

        // `inventory_movements` là nguồn dữ liệu gốc của tồn kho.
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->string('movement_type', 30);
            $table->string('source_type', 30);
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_code', 50)->nullable();
            $table->decimal('quantity_change', 18, 3);
            $table->decimal('unit_cost', 18, 2)->default(0);
            $table->decimal('total_cost', 18, 2)->default(0);
            $table->dateTime('movement_date');
            $table->string('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['business_id', 'warehouse_id', 'product_id', 'movement_date', 'id'], 'idx_inventory_movements_lookup');
            $table->index(['source_type', 'source_id']);
        });

        // `current_stocks` là bảng tổng hợp để đọc nhanh tồn hiện tại, không phải source of truth.
        Schema::create('current_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('quantity_on_hand', 18, 3)->default(0);
            $table->decimal('avg_unit_cost', 18, 2)->default(0);
            $table->decimal('stock_value', 18, 2)->default(0);
            $table->dateTime('last_movement_at')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'warehouse_id', 'product_id']);
            $table->index(['business_id', 'product_id']);
        });
    }

    public function down(): void
    {
        // Xóa read model trước, ledger sau, cuối cùng mới xóa payment.
        Schema::dropIfExists('current_stocks');
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('payments');
    }
};
