<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tạo bảng phiếu kho tổng.
     */
    public function up(): void
    {
        Schema::create('warehouse_documents', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->string('document_code');
            $table->enum('document_type', ['import', 'export']);
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->date('document_date');
            $table->enum('status', ['draft', 'confirmed', 'cancelled'])->default('draft');
            $table->string('reference_code')->nullable();
            $table->decimal('subtotal_amount', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->text('note')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['business_id', 'document_code']);
            $table->index('business_id');
            $table->index('warehouse_id');
            $table->index('document_date');
            $table->index('status');
            $table->index('document_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_documents');
    }
};
