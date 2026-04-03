<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('description', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['business_id', 'name']);
            $table->index(['business_id', 'is_active']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('unit_id')->constrained('categories')->nullOnDelete();
            $table->index(['business_id', 'category_id']);
        });

        Schema::table('warehouses', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('address');
        });

        DB::table('warehouses')->update([
            'is_active' => DB::raw("CASE WHEN status = 'inactive' THEN 0 ELSE 1 END"),
        ]);

        Schema::table('warehouses', function (Blueprint $table) {
            $table->index(['business_id', 'is_active']);
            $table->dropIndex('warehouses_business_id_status_index');
            $table->dropColumn('status');
        });
    }

    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->string('status', 30)->default('active')->after('address');
        });

        DB::table('warehouses')->update([
            'status' => DB::raw('CASE WHEN is_active = 1 THEN "active" ELSE "inactive" END'),
        ]);

        Schema::table('warehouses', function (Blueprint $table) {
            $table->index(['business_id', 'status']);
            $table->dropIndex('warehouses_business_id_is_active_index');
            $table->dropColumn('is_active');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_business_id_category_id_index');
            $table->dropConstrainedForeignId('category_id');
        });

        Schema::dropIfExists('categories');
    }
};
