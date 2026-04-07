<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tạo bảng quản lý bộ đếm theo từng business và từng scope.
     *
     * Bảng này là nền chung cho các mã tự sinh như:
     * - SKU sản phẩm
     * - mã chứng từ
     * - mã nội bộ của unit, warehouse...
     */
    public function up(): void
    {
        Schema::create('business_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->string('scope', 100);
            $table->string('prefix', 20);
            $table->unsignedBigInteger('current_value')->default(0);
            $table->timestamps();

            $table->unique(['business_id', 'scope']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_sequences');
    }
};
