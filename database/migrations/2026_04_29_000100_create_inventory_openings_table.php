<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_openings', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->string('product_name');
            $table->foreignId('unit_id')->constrained('units')->restrictOnDelete();
            $table->string('unit_name');
            $table->date('opening_date');
            $table->decimal('quantity', 18, 2)->default(0);
            $table->decimal('unit_cost', 18, 2)->default(0);
            $table->decimal('total_cost', 18, 2)->default(0);
            $table->text('note')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['business_id', 'warehouse_id', 'product_id'], 'uq_io_business_warehouse_product');
            $table->index('business_id');
            $table->index('warehouse_id');
            $table->index('product_id');
            $table->index('opening_date');
            $table->index(['business_id', 'warehouse_id'], 'idx_io_business_warehouse');
            $table->index(['business_id', 'product_id'], 'idx_io_business_product');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_openings');
    }
};
