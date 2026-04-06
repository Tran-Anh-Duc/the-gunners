<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private array $businessScopedTables = [
        'units',
        'warehouses',
        'categories',
        'customers',
        'suppliers',
        'products',
    ];

    private array $globalTables = [
        'businesses',
        'users',
    ];

    public function up(): void
    {
        foreach ($this->businessScopedTables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->string('name_slug')->nullable();
                $table->index(['business_id', 'name_slug'], $this->businessIndexName($tableName));
            });
        }

        foreach ($this->globalTables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->string('name_slug')->nullable();
                $table->index('name_slug', $this->globalIndexName($tableName));
            });
        }

        foreach (array_merge($this->businessScopedTables, $this->globalTables) as $tableName) {
            DB::table($tableName)
                ->select(['id', 'name'])
                ->orderBy('id')
                ->chunkById(100, function ($rows) use ($tableName): void {
                    foreach ($rows as $row) {
                        DB::table($tableName)
                            ->where('id', $row->id)
                            ->update([
                                'name_slug' => Str::slug((string) $row->name),
                            ]);
                    }
                });
        }
    }

    public function down(): void
    {
        foreach ($this->businessScopedTables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->dropIndex($this->businessIndexName($tableName));
                $table->dropColumn('name_slug');
            });
        }

        foreach ($this->globalTables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->dropIndex($this->globalIndexName($tableName));
                $table->dropColumn('name_slug');
            });
        }
    }

    private function businessIndexName(string $tableName): string
    {
        return "{$tableName}_business_id_name_slug_index";
    }

    private function globalIndexName(string $tableName): string
    {
        return "{$tableName}_name_slug_index";
    }
};
