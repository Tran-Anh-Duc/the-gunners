<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        if (! Schema::hasColumn('products', 'is_active')) {
            Schema::table('products', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('sale_price');
            });
        }

        if (Schema::hasColumn('products', 'status')) {
            DB::table('products')->update([
                'is_active' => DB::raw("CASE WHEN status = 'inactive' THEN 0 ELSE 1 END"),
            ]);

            $this->dropIndexIfExists('products', ['business_id', 'status']);

            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        } elseif (! $this->indexExists('products', 'products_business_id_is_active_index')) {
            Schema::table('products', function (Blueprint $table) {
                $table->index(['business_id', 'is_active']);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        if (! Schema::hasColumn('products', 'status')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('status', 30)->default('active')->after('sale_price');
            });
        }

        if (Schema::hasColumn('products', 'is_active')) {
            DB::table('products')->update([
                'status' => DB::raw("CASE WHEN is_active = 1 THEN 'active' ELSE 'inactive' END"),
            ]);

            $this->dropIndexIfExists('products', ['business_id', 'is_active']);

            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        } elseif (! $this->indexExists('products', 'products_business_id_status_index')) {
            Schema::table('products', function (Blueprint $table) {
                $table->index(['business_id', 'status']);
            });
        }
    }

    protected function dropIndexIfExists(string $table, array $columns): void
    {
        try {
            Schema::table($table, function (Blueprint $table) use ($columns) {
                $table->dropIndex($columns);
            });
        } catch (Throwable) {
            // SQLite và một số môi trường test không expose metadata index giống MySQL.
        }
    }

    protected function indexExists(string $table, string $indexName): bool
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('{$table}')");

            return collect($indexes)->contains(fn ($index) => ($index->name ?? null) === $indexName);
        }

        if ($driver === 'mysql') {
            $databaseName = DB::getDatabaseName();

            return DB::table('information_schema.statistics')
                ->where('table_schema', $databaseName)
                ->where('table_name', $table)
                ->where('index_name', $indexName)
                ->exists();
        }

        return false;
    }
};
