<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Distributor extends Model
{

    protected $table = "distributors";
    protected $fillable = ['name', 'slug', 'group_code', 'parent_id'];

    public function personalSales()
    {
        return $this->hasMany(PersonalSale::class, 'distributor_id');
    }

    /*funtion sử dụng quan hệ*/ // C1
    public static function getAllWithTotalSalesModel()
    {
        return Distributor::withSum('personalSales','price')->get()->toArray();
    }

    /*funtion sử dụng đệ quy */ // C2
    /*
     * B1 : mục 'months' => mình sử lý lấy các tháng từ thời điểm hiện tại , T , T-1 , T-2
     * B2 : mục 'closure' mình tạo ra các key base  để gom  các nhóm phân cấp
     * B3 : mục 'ps' mình tính tổng doanh thu của từng cá nhân theo mục 'months'
     * B4 : mục 'grp' mình tính tỏng doanh thu đội nhóm theo  'closure' và join ps  , theo từng NPP  , để gán doanh thu
     * B5 : mục select cuối , mình tính tổng sum của từng cá nhân và đội nhóm , theo yêu cầu đề bài  : cá nhân > 5 triệu trong 3 tháng  , đội nhóm > 250 triệu trong 3 tháng.
     */
    public static function getAllWithTotalSales()
    {
        $rows = DB::select(<<<'SQL'
            WITH RECURSIVE
                months AS (
                  SELECT YEAR(CURDATE()) y0,
                         MONTH(CURDATE()) m0,
                         YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) y1,
                         MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) m1,
                         YEAR(DATE_SUB(CURDATE(), INTERVAL 2 MONTH)) y2,
                         MONTH(DATE_SUB(CURDATE(), INTERVAL 2 MONTH)) m2
                ),

                closure AS (
                  SELECT
                        id AS ancestor,
                        id AS descendant
                  FROM
                        distributors
                  UNION ALL
                  SELECT
                        c.ancestor, d.id
                  FROM
                        closure c
                  JOIN distributors d ON d.parent_id = c.descendant
                ),

                ps AS (
                  SELECT
                        distributor_id,
                        year,
                        month,
                        SUM(price) s
                  FROM
                        personal_sales
                  WHERE (year, month) IN (
                    SELECT y0, m0
                    FROM months
                    UNION ALL SELECT y1, m1 FROM months
                    UNION ALL SELECT y2, m2 FROM months
                  )
                  GROUP BY
                        distributor_id, year, month
                ),

                grp AS (
                  SELECT
                        cl.ancestor AS distributor_id,
                        ps.year,
                        ps.month,
                        SUM(ps.s) gs
                  FROM
                        closure cl
                  JOIN
                        ps ON ps.distributor_id = cl.descendant
                  GROUP BY
                        cl.ancestor, ps.year, ps.month
                )
                SELECT
                  d.id AS distributor_id, d.name, d.parent_id, d.group_code,

                  COALESCE(SUM(CASE WHEN ps.year=(SELECT y0 FROM months) AND ps.month=(SELECT m0 FROM months) THEN ps.s END),0) AS sum_T,
                  COALESCE(SUM(CASE WHEN ps.year=(SELECT y1 FROM months) AND ps.month=(SELECT m1 FROM months) THEN ps.s END),0) AS sum_T_1,
                  COALESCE(SUM(CASE WHEN ps.year=(SELECT y2 FROM months) AND ps.month=(SELECT m2 FROM months) THEN ps.s END),0) AS sum_T_2,

                  COALESCE(SUM(CASE WHEN grp.year=(SELECT y0 FROM months) AND grp.month=(SELECT m0 FROM months) THEN grp.gs END),0) AS grp_T,
                  COALESCE(SUM(CASE WHEN grp.year=(SELECT y1 FROM months) AND grp.month=(SELECT m1 FROM months) THEN grp.gs END),0) AS grp_T_1,
                  COALESCE(SUM(CASE WHEN grp.year=(SELECT y2 FROM months) AND grp.month=(SELECT m2 FROM months) THEN grp.gs END),0) AS grp_T_2
                FROM distributors d
                LEFT JOIN ps  ON ps.distributor_id  = d.id
                LEFT JOIN grp ON grp.distributor_id = d.id
                GROUP BY d.id, d.name, d.parent_id, d.group_code
                HAVING
                  (sum_T  > 5000000 AND sum_T_1  > 5000000 AND sum_T_2  > 5000000)
                  OR
                  (grp_T  > 250000000 AND grp_T_1 > 250000000 AND grp_T_2 > 250000000)
                ORDER BY distributor_id
            SQL
        );

        return $rows;
    }


    public function scopeQualifiedForMonth(Builder $q, int $month, int $year): Builder
    {
        return $q->withSum(['personalSales as month_sum' => function ($s) use ($month, $year) {
            $s->where('month', $month)->where('year', $year);
        }], 'price')
            ->having('month_sum', '>', 0); // dùng HAVING để lọc theo alias
    }


}
