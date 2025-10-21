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
        Schema::table('user_department', function (Blueprint $table) {
            $table->string('is_main')->nullable(); /// làm chính ở phòng ban nào  
            $table->string('position')->nullable();  /// vị trí làm ở phòng ban
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_department', function (Blueprint $table) {
            $table->dropColumn('is_main');
            $table->dropColumn('position');
        });
    }
};
