<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tạo bảng ledger biến động tồn kho.
     */
    public function up(): void
    {
        Schema::create('inventory_stock_movements', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('unit_id')->constrained('units')->restrictOnDelete();
            $table->string('source_type', 50);
            $table->unsignedBigInteger('source_id');
            $table->unsignedBigInteger('source_line_id');
            $table->string('movement_type', 50);
            $table->date('movement_date');
            $table->timestamp('posted_at');
            $table->decimal('quantity_delta', 18, 2)->default(0);
            $table->decimal('unit_cost', 18, 2)->default(0);
            $table->decimal('value_delta', 18, 2)->default(0);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(
                ['business_id', 'source_type', 'source_id', 'source_line_id', 'warehouse_id', 'product_id'],
                'uq_ism_source_line_stock'
            );
            $table->index(['business_id', 'warehouse_id', 'product_id', 'movement_date'], 'idx_ism_stock_lookup');
            $table->index(['business_id', 'product_id', 'movement_date'], 'idx_ism_product_movement_date');
            $table->index(['business_id', 'source_type', 'source_id'], 'idx_ism_source_lookup');
            $table->index(['business_id', 'posted_at'], 'idx_ism_business_posted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_stock_movements');
    }
};
