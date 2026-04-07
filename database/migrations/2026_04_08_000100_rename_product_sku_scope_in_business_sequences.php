<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('business_sequences')
            ->where('scope', 'product_sku')
            ->update(['scope' => 'product.sku']);
    }

    public function down(): void
    {
        DB::table('business_sequences')
            ->where('scope', 'product.sku')
            ->update(['scope' => 'product_sku']);
    }
};
