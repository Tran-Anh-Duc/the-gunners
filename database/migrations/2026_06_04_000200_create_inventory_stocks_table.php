<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tạo bảng snapshot tồn kho hiện tại.
     */
    public function up(): void
    {
        Schema::create('inventory_stocks', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('quantity_on_hand', 18, 2)->default(0);
            $table->decimal('avg_unit_cost', 18, 2)->default(0);
            $table->decimal('inventory_value', 18, 2)->default(0);
            $table->foreignId('last_movement_id')
                ->nullable()
                ->constrained('inventory_stock_movements')
                ->nullOnDelete();
            $table->timestamp('last_movement_at')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'warehouse_id', 'product_id'], 'uq_is_business_warehouse_product');
            $table->index(['business_id', 'warehouse_id'], 'idx_is_business_warehouse');
            $table->index(['business_id', 'product_id'], 'idx_is_business_product');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_stocks');
    }
};
