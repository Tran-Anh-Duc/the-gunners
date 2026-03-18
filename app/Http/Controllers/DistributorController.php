<?php

namespace App\Http\Controllers;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DistributorController extends BaseController
{
    public function index(): View
    {
        $distributors = $this->getDistributors();

        return view('distributors.index', compact('distributors'));
    }

    protected function getDistributors(): Collection
    {
        if (! Schema::hasTable('distributors')) {
            return collect();
        }

        return DB::table('distributors')
            ->select([
                'distributor_id',
                'name',
                'parent_id',
                'group_code',
                'sum_T',
                'sum_T_1',
                'sum_T_2',
                'grp_T',
                'grp_T_1',
                'grp_T_2',
            ])
            ->orderBy('distributor_id')
            ->get();
    }
}
