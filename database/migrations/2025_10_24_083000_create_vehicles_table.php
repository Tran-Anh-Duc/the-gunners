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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('plate_number', 20)->unique()->comment('Biển số xe');
            $table->string('type', 50)->nullable()->comment('Loại xe (xe máy, xe tải, container,...)');
            $table->decimal('capacity', 10, 2)->nullable()->comment('Sức chứa (tấn hoặc m³)');
            $table->unsignedBigInteger('status_id')->default(1)->comment('Trạng thái xe - FK -> statuses');
            $table->timestamps();

            // Khóa ngoại
            $table->foreign('status_id')->references('id')->on('statuses')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
