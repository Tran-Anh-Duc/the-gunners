<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tạo bảng chi tiết phiếu kho.
     */
    public function up(): void
    {
        Schema::create('warehouse_document_details', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->foreignId('warehouse_document_id')->constrained('warehouse_documents')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->string('product_name');
            $table->foreignId('unit_id')->constrained('units')->restrictOnDelete();
            $table->string('unit_name');
            $table->decimal('quantity', 18, 2)->default(0);
            $table->decimal('unit_price', 18, 2)->default(0);
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_price', 18, 2)->default(0);
            $table->decimal('total_price', 18, 2)->default(0);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['warehouse_document_id', 'product_id'], 'uq_wdd_document_product');
            $table->index('warehouse_document_id', 'idx_wdd_document_id');
            $table->index('product_id', 'idx_wdd_product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_document_details');
    }
};
