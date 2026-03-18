<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tạo các bảng lõi cho mô hình multi-tenant.
     *
     * Nhóm bảng này là nền tảng của toàn bộ ứng dụng:
     * - `businesses`: tenant hoặc shop;
     * - `users`: tài khoản hệ thống;
     * - `business_users`: membership theo business;
     * - `business_modules`: module được bật cho từng business.
     */
    public function up(): void
    {
        // `businesses` là tenant gốc, mỗi shop sẽ map vào một business.
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('plan_code', 50)->nullable();
            $table->string('status', 30)->default('active')->index();
            $table->string('currency_code', 10)->default('VND');
            $table->string('timezone', 50)->default('Asia/Ho_Chi_Minh');
            $table->timestamps();
            $table->softDeletes();
        });

        // `users` là tài khoản dùng chung toàn hệ thống, chưa gắn tenant trực tiếp.
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('phone', 20)->nullable();
            $table->string('avatar')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->dateTime('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        // `business_users` là membership dùng để gắn user vào business và lưu role hiện tại.
        Schema::create('business_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 30)->default('staff');
            $table->string('status', 30)->default('active');
            $table->boolean('is_owner')->default(false);
            $table->dateTime('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'user_id']);
            $table->index(['business_id', 'role']);
        });

        // `business_modules` dùng để bật hoặc tắt module theo gói dịch vụ của từng business.
        Schema::create('business_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->string('module_code', 50);
            $table->string('status', 30)->default('active');
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'module_code']);
        });
    }

    public function down(): void
    {
        // Rollback theo thứ tự ngược lại để tránh vướng khóa ngoại.
        Schema::dropIfExists('business_modules');
        Schema::dropIfExists('business_users');
        Schema::dropIfExists('users');
        Schema::dropIfExists('businesses');
    }
};
