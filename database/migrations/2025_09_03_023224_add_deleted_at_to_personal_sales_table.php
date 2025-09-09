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
        Schema::table('personal_sales', function (Blueprint $table) {
            $table->year('year')->nullable()->comment('Năm');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personal_sales', function (Blueprint $table) {
            $table->dropColumn('year');
        });
    }
};
