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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable()->comment('Tên nhà cung cấp');
            $table->string('contact_name')->nullable()->comment('Người liên hệ');
            $table->string('phone')->nullable()->comment('SĐT');
            $table->string('email')->nullable()->comment('Email');
            $table->string('address')->nullable()->comment('Địa chỉ');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
