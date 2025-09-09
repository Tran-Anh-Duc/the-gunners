<?php

namespace App\Http\Controllers\Api;

use App\Console\Commands\DistributeBonus;
use App\Models\Distributor;
use App\Models\BonusDistribution;
use App\Models\PersonalSale;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DistributorController extends Controller
{

    public function index1()
    {
        $distributors = Distributor::getAllWithTotalSales();

        $data = [];
        foreach ($distributors as $key => $value) {
            if (!isset($data[$value['parent_id']])){

                if ($value['id'] == $value['parent_id']){
                    $data[$value['parent_id']]['id'] = $value;
                }else{
                    $data[$value['parent_id']]['id']  = $value;
                }
            }else{
                if ($value['id'] == $value['parent_id']){
                    $data[$value['parent_id']]['id'] = $value;
                }else{
                    $data[$value['parent_id']]['detail'][]  = $value;
                }
            }
        }
        $total = 0;
        foreach ($data as $key => $value){

            if ($value['id']['personal_sales_sum_price'] < 5000000){
                unset($data[$key]);
            }

        }

        foreach ($data as $key => $value){

            foreach ($value['detail'] as $k => $v){
                $total += $v['personal_sales_sum_price'];
            }
            $data[$key]['total_detail'] = $total;
        }

        foreach ($data as $key => $value){

            if ($value['total_detail'] < 250000000){
                unset($data[$key]);
            }
        }


        $result = [];

        foreach ($data as $key => $value) {
            if (isset($value['id'])) {
                $result[] = $value['id'];
            }

            if (!empty($value['detail'])) {
                foreach ($value['detail'] as $child) {
                    $result[] = $child;
                }
            }
        }

        $idData = [];
        foreach ($result as $key => $value){
            $idData[] = $value['id'];
        }

        $dataBonus = BonusDistribution::query()->whereIn('distributor_id',$idData)->get();

        $bonusMap = [];
        foreach ($dataBonus as $v) {
            $bonusMap[$v['distributor_id']] = ($bonusMap[$v['distributor_id']] ?? 0) + $v['amount'];
        }


        foreach ($result as $key => $value) {
            $result[$key]['bonus'] = $bonusMap[$value['id']] ?? 0;
        }

        return view('distributors.index', [
            'distributors' => $result
        ]);
    }

    public function index()
    {
        $distributors = Distributor::getAllWithTotalSales();

        foreach ( $distributors as $key => $value){

        }

        return view('distributors.index', [
            'distributors' => $distributors,
        ]);
    }



}
