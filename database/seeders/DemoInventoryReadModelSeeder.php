<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\CurrentStock;
use App\Models\InventoryMovement;
use App\Models\StockAdjustment;
use App\Models\StockIn;
use App\Models\StockOut;
use App\Models\User;
use RuntimeException;

class DemoInventoryReadModelSeeder extends MvpInventorySeeder
{
    public function run(): void
    {
        $business = Business::query()->where('code', 'demo-store')->first();

        if (! $business) {
            throw new RuntimeException('Demo business not found.');
        }

        $manager = User::query()->where('email', 'manager@demo-store.local')->first();
        $staff = User::query()->where('email', 'staff@demo-store.local')->first();

        if (! $manager || ! $staff) {
            throw new RuntimeException('Demo users required for rebuilding inventory read models were not found.');
        }

        InventoryMovement::query()->where('business_id', $business->id)->delete();
        CurrentStock::query()->where('business_id', $business->id)->delete();

        $documents = [
            'stock_in' => StockIn::query()
                ->with('items.product')
                ->where('business_id', $business->id)
                ->orderBy('stock_in_date')
                ->get()
                ->all(),
            'stock_out' => StockOut::query()
                ->with('items.product')
                ->where('business_id', $business->id)
                ->orderBy('stock_out_date')
                ->get()
                ->all(),
            'adjustments' => StockAdjustment::query()
                ->with('items.product')
                ->where('business_id', $business->id)
                ->orderBy('adjustment_date')
                ->get()
                ->all(),
        ];

        $this->seedInventoryReadModels(
            $business,
            [
                'manager' => $manager,
                'staff' => $staff,
            ],
            [],
            [],
            $documents,
        );
    }
}
