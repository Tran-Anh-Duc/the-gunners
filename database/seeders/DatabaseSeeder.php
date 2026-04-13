<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeder gốc của ứng dụng.
 *
 * Ở giai đoạn hiện tại, dự án chỉ cần một bộ dữ liệu demo nền
 * để phục vụ kiểm thử thủ công và onboarding nhanh môi trường local.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Chạy seed dữ liệu mặc định cho toàn bộ ứng dụng.
     */
    public function run(): void
    {
        // Seed một bộ dữ liệu nền bám đúng scope đã được giữ lại trong dự án.
        $this->call(MvpInventorySeeder::class);
        $this->call(WarehouseDocumentSeeder::class);
    }
}
