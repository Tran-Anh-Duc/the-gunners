<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DistributeBonus extends Command
{
    protected $signature = 'bonus:distribute {month?} {year?}';
    protected $description = 'Chia quỹ thưởng đồng chia = 1% tổng doanh số tháng T cho các NPP đủ điều kiện (có sales tháng T)';

    public function handle()
    {
        // 1) Resolve tháng/năm + validate
        $month = (int)($this->argument('month') ?? Carbon::now()->month);
        $year  = (int)($this->argument('year')  ?? Carbon::now()->year);

        if ($month < 1 || $month > 12) {
            $this->error("Tháng không hợp lệ: $month (1..12)");
            return 1;
        }
        if ($year < 2000 || $year > 2100) {
            $this->error("Năm không hợp lệ: $year");
            return 1;
        }

        $this->info("Bắt đầu tính thưởng cho tháng $month/$year ...");

        // 2) Tổng doanh số toàn hệ thống tháng T (số nguyên)
        $totalSales = DB::table('personal_sales')
            ->where('month', $month)
            ->where('year',  $year)
            ->sum('price'); // BIGINT -> integer

        if ($totalSales <= 0) {
            $this->warn("Không có doanh số tháng này.");
            return 0;
        }

        // 3) Quỹ thưởng = 1% tổng doanh số (dùng số nguyên để tránh float)
        //   VD: 1% = chia cho 100
        $fund = intdiv((int)$totalSales, 100); // tương đương floor($totalSales / 100)

        // 4) Danh sách NPP đủ điều kiện: tổng sales tháng T > 0
        //    Đồng thời tính tổng để xếp hạng phân dư
        $qualified = DB::table('personal_sales')
            ->select('distributor_id', DB::raw('SUM(price) as sum_price'))
            ->where('month', $month)
            ->where('year',  $year)
            ->groupBy('distributor_id')
            ->having('sum_price', '>', 5000000)
            ->orderByDesc('sum_price') // dùng để chia phần dư công bằng
            ->get();

        $count = $qualified->count();
        if ($count === 0) {
            $this->warn("Không có NPP đủ điều kiện.");
            return 0;
        }

        // 5) Tính phần chia và phần dư
        $bonusEach = intdiv($fund, $count);   // phần chia đều
        $remainder = $fund % $count;          // phần dư còn lại

        // 6) Ghi DB an toàn + hiệu quả (upsert batch) trong transaction
        DB::transaction(function () use ($qualified, $month, $year, $bonusEach, $remainder) {
            $payload = [];
            $i = 0;

            foreach ($qualified as $row) {
                // phân phần dư +1 cho $remainder người đầu (theo thứ tự doanh số tháng giảm dần)
                $amount = $bonusEach + ($i < $remainder ? 1 : 0);

                $payload[] = [
                    'distributor_id' => $row->distributor_id,
                    'month'          => $month,
                    'year'           => $year,
                    'amount'         => $amount,
                    'updated_at'     => now(),
                    'created_at'     => now(),
                ];
                $i++;
            }

            // Yêu cầu có unique index (distributor_id, month, year)
            // để upsert an toàn khi chạy lại
            DB::table('bonus_distributions')->upsert(
                $payload,
                ['distributor_id', 'month', 'year'], // unique key
                ['amount', 'updated_at']             // cột update khi trùng
            );
        });

        $this->info("Quỹ: " . number_format($fund) . " | NPP đủ điều kiện: $count | Mỗi người: " . number_format($bonusEach) . " (+ phân dư $remainder)");
        return 0;
    }
}
