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
        // Bước 1: thêm cột (nullable để không vỡ dữ liệu cũ)
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'department_id')) {
                $table->unsignedBigInteger('department_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('users', 'status_id')) {
                $table->unsignedBigInteger('status_id')->nullable()->after('department_id');
            }
        });

        // Bước 2: gắn FK (đảm bảo bảng cha tồn tại + dữ liệu hợp lệ)
        Schema::table('users', function (Blueprint $table) {
            // Đổi 'users_status' thành 'statuses' nếu bảng bạn tên 'statuses'
            $table->foreign('department_id')
                ->references('id')->on('departments')
                ->nullOnDelete()->cascadeOnUpdate();

            $table->foreign('status_id')
                ->references('id')->on('users_status') // hoặc 'statuses'
                ->restrictOnDelete()->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Gỡ FK theo cột (an toàn hơn)
            if (Schema::hasColumn('users', 'department_id')) {
                $table->dropForeign(['department_id']);
            }
            if (Schema::hasColumn('users', 'status_id')) {
                $table->dropForeign(['status_id']);
            }

            // Rồi mới drop cột
            if (Schema::hasColumn('users', 'status_id')) {
                $table->dropColumn('status_id');
            }
            if (Schema::hasColumn('users', 'department_id')) {
                $table->dropColumn('department_id');
            }
        });
    }
};
