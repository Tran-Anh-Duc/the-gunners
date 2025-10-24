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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Tên khách hàng');
            $table->string('phone', 20)->nullable()->comment('Số điện thoại');
            $table->string('email', 100)->nullable()->comment('Email');
            $table->string('address')->nullable()->comment('Địa chỉ giao hàng');
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
