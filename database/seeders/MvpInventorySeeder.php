<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\BusinessModule;
use App\Models\BusinessUser;
use App\Models\CurrentStock;
use App\Models\Customer;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockIn;
use App\Models\StockInItem;
use App\Models\StockOut;
use App\Models\StockOutItem;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MvpInventorySeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $business = $this->seedBusiness();
            $users = $this->seedUsers();

            $this->resetDemoBusinessData($business->id);
            $this->seedMemberships($business, $users);
            $this->seedModules($business);

            $units = $this->seedUnits($business);
            $warehouses = $this->seedWarehouses($business);
            $customers = $this->seedCustomers($business);
            $suppliers = $this->seedSuppliers($business);
            $products = $this->seedProducts($business, $units);

            $documents = $this->seedTransactions($business, $users, $warehouses, $customers, $suppliers, $products);
            $this->seedInventoryReadModels($business, $users, $warehouses, $products, $documents);
        });
    }

    protected function seedBusiness(): Business
    {
        $business = Business::withTrashed()->updateOrCreate(
            ['code' => 'demo-store'],
            [
                'name' => 'Demo Store',
                'phone' => '0901000100',
                'email' => 'owner@demo-store.local',
                'address' => '123 Nguyen Trai, Ho Chi Minh',
                'plan_code' => 'starter',
                'status' => 'active',
                'currency_code' => 'VND',
                'timezone' => 'Asia/Ho_Chi_Minh',
            ]
        );

        if ($business->trashed()) {
            $business->restore();
        }

        return $business;
    }

    /**
     * @return array<string, User>
     */
    protected function seedUsers(): array
    {
        $users = [
            'owner' => [
                'name' => 'Demo Owner',
                'email' => 'owner@demo-store.local',
                'phone' => '0901000100',
                'is_active' => true,
            ],
            'manager' => [
                'name' => 'Demo Manager',
                'email' => 'manager@demo-store.local',
                'phone' => '0901000101',
                'is_active' => true,
            ],
            'staff' => [
                'name' => 'Demo Staff',
                'email' => 'staff@demo-store.local',
                'phone' => '0901000102',
                'is_active' => true,
            ],
        ];

        foreach ($users as $key => $data) {
            $user = User::withTrashed()->updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'password' => Hash::make('password'),
                    'is_active' => $data['is_active'],
                    'last_login_at' => CarbonImmutable::now()->subDay(),
                ]
            );

            if ($user->trashed()) {
                $user->restore();
            }

            $users[$key] = $user;
        }

        return $users;
    }

    protected function resetDemoBusinessData(int $businessId): void
    {
        CurrentStock::query()->where('business_id', $businessId)->delete();
        InventoryMovement::query()->where('business_id', $businessId)->delete();
        Payment::query()->where('business_id', $businessId)->delete();
        StockAdjustmentItem::query()->where('business_id', $businessId)->delete();
        StockAdjustment::query()->where('business_id', $businessId)->delete();
        StockOutItem::query()->where('business_id', $businessId)->delete();
        StockOut::query()->where('business_id', $businessId)->delete();
        StockInItem::query()->where('business_id', $businessId)->delete();
        StockIn::query()->where('business_id', $businessId)->delete();
        OrderItem::query()->where('business_id', $businessId)->delete();
        Order::query()->where('business_id', $businessId)->delete();

        Product::withTrashed()->where('business_id', $businessId)->forceDelete();
        Supplier::withTrashed()->where('business_id', $businessId)->forceDelete();
        Customer::withTrashed()->where('business_id', $businessId)->forceDelete();
        Warehouse::withTrashed()->where('business_id', $businessId)->forceDelete();
        Unit::withTrashed()->where('business_id', $businessId)->forceDelete();
        BusinessModule::query()->where('business_id', $businessId)->delete();
        BusinessUser::query()->where('business_id', $businessId)->delete();
    }

    /**
     * @param  array<string, User>  $users
     */
    protected function seedMemberships(Business $business, array $users): void
    {
        $joinedAt = CarbonImmutable::now()->subMonths(2);

        BusinessUser::query()->create([
            'business_id' => $business->id,
            'user_id' => $users['owner']->id,
            'role' => 'owner',
            'status' => 'active',
            'is_owner' => true,
            'joined_at' => $joinedAt,
        ]);

        BusinessUser::query()->create([
            'business_id' => $business->id,
            'user_id' => $users['manager']->id,
            'role' => 'manager',
            'status' => 'active',
            'is_owner' => false,
            'joined_at' => $joinedAt->addDays(3),
        ]);

        BusinessUser::query()->create([
            'business_id' => $business->id,
            'user_id' => $users['staff']->id,
            'role' => 'staff',
            'status' => 'active',
            'is_owner' => false,
            'joined_at' => $joinedAt->addDays(7),
        ]);
    }

    protected function seedModules(Business $business): void
    {
        $startedAt = CarbonImmutable::now()->subMonth();

        foreach (['products', 'inventory', 'orders', 'customers', 'suppliers', 'payments'] as $moduleCode) {
            BusinessModule::query()->create([
                'business_id' => $business->id,
                'module_code' => $moduleCode,
                'status' => 'active',
                'starts_at' => $startedAt,
                'ends_at' => null,
            ]);
        }
    }

    /**
     * @return array<string, Unit>
     */
    protected function seedUnits(Business $business): array
    {
        $units = [
            'pcs' => [
                'code' => 'PCS',
                'name' => 'Cai',
                'description' => 'Don vi ban le mac dinh',
            ],
            'box' => [
                'code' => 'BOX',
                'name' => 'Hop',
                'description' => 'Dong goi theo hop',
            ],
        ];

        foreach ($units as $key => $data) {
            $units[$key] = Unit::query()->create([
                'business_id' => $business->id,
                'code' => $data['code'],
                'name' => $data['name'],
                'description' => $data['description'],
                'is_active' => true,
            ]);
        }

        return $units;
    }

    /**
     * @return array<string, Warehouse>
     */
    protected function seedWarehouses(Business $business): array
    {
        $warehouses = [
            'main' => [
                'code' => 'WH-MAIN',
                'name' => 'Kho chinh',
                'address' => 'Tang tret - 123 Nguyen Trai',
            ],
            'online' => [
                'code' => 'WH-ONLINE',
                'name' => 'Kho don online',
                'address' => 'Tang 2 - 123 Nguyen Trai',
            ],
        ];

        foreach ($warehouses as $key => $data) {
            $warehouses[$key] = Warehouse::query()->create([
                'business_id' => $business->id,
                'code' => $data['code'],
                'name' => $data['name'],
                'address' => $data['address'],
                'status' => 'active',
            ]);
        }

        return $warehouses;
    }

    /**
     * @return array<string, Customer>
     */
    protected function seedCustomers(Business $business): array
    {
        $customers = [
            'linh' => [
                'name' => 'Nguyen Thi Linh',
                'phone' => '0902333444',
                'email' => 'linh.customer@example.com',
                'address' => 'Go Vap, Ho Chi Minh',
                'note' => 'Khach mua tu Facebook',
            ],
            'quang' => [
                'name' => 'Tran Minh Quang',
                'phone' => '0902666777',
                'email' => 'quang.customer@example.com',
                'address' => 'Thu Duc, Ho Chi Minh',
                'note' => 'Khach mua si nho',
            ],
            'nhi' => [
                'name' => 'Pham Bao Nhi',
                'phone' => '0902999888',
                'email' => 'nhi.customer@example.com',
                'address' => 'Quan 7, Ho Chi Minh',
                'note' => 'Khach mua lai nhieu lan',
            ],
        ];

        foreach ($customers as $key => $data) {
            $customers[$key] = Customer::query()->create([
                'business_id' => $business->id,
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'address' => $data['address'],
                'note' => $data['note'],
                'is_active' => true,
            ]);
        }

        return $customers;
    }

    /**
     * @return array<string, Supplier>
     */
    protected function seedSuppliers(Business $business): array
    {
        $suppliers = [
            'smart' => [
                'name' => 'Smart Accessories Co.',
                'contact_name' => 'Le Hoang',
                'phone' => '02838889999',
                'email' => 'sales@smart-accessories.local',
                'address' => 'Binh Tan, Ho Chi Minh',
                'note' => 'Nha cung cap phu kien dien thoai',
            ],
            'packing' => [
                'name' => 'Packing Hub',
                'contact_name' => 'Vo Mai',
                'phone' => '02837775555',
                'email' => 'hello@packing-hub.local',
                'address' => 'Tan Phu, Ho Chi Minh',
                'note' => 'Nha cung cap vat tu dong goi',
            ],
        ];

        foreach ($suppliers as $key => $data) {
            $suppliers[$key] = Supplier::query()->create([
                'business_id' => $business->id,
                'name' => $data['name'],
                'contact_name' => $data['contact_name'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'address' => $data['address'],
                'note' => $data['note'],
                'is_active' => true,
            ]);
        }

        return $suppliers;
    }

    /**
     * @param  array<string, Unit>  $units
     * @return array<string, Product>
     */
    protected function seedProducts(Business $business, array $units): array
    {
        $products = [
            'cable' => [
                'sku' => 'SKU-CABLE-1M',
                'name' => 'Cap sac Type-C 1m',
                'barcode' => '8938501000011',
                'cost_price' => 25000,
                'sale_price' => 49000,
                'description' => 'Day cap sac thong dung cho shop online',
            ],
            'charger' => [
                'sku' => 'SKU-CHARGER-20W',
                'name' => 'Cu sac nhanh 20W',
                'barcode' => '8938501000012',
                'cost_price' => 120000,
                'sale_price' => 189000,
                'description' => 'Cu sac nhanh dong chu luc ban chay',
            ],
            'powerbank' => [
                'sku' => 'SKU-PBANK-10K',
                'name' => 'Pin du phong 10000mAh',
                'barcode' => '8938501000013',
                'cost_price' => 210000,
                'sale_price' => 329000,
                'description' => 'Pin du phong danh cho nguoi ban online',
            ],
            'bubble-wrap' => [
                'sku' => 'SKU-BWRAP-50M',
                'name' => 'Xop hoi dong hang 50m',
                'barcode' => '8938501000014',
                'cost_price' => 15000,
                'sale_price' => 29000,
                'description' => 'Vat tu dong goi cho don hang',
            ],
            'stand' => [
                'sku' => 'SKU-STAND-ALU',
                'name' => 'Gia do dien thoai nhom',
                'barcode' => '8938501000015',
                'cost_price' => 35000,
                'sale_price' => 69000,
                'description' => 'San pham them de upsell',
            ],
        ];

        foreach ($products as $key => $data) {
            $products[$key] = Product::query()->create([
                'business_id' => $business->id,
                'unit_id' => $units['pcs']->id,
                'sku' => $data['sku'],
                'name' => $data['name'],
                'barcode' => $data['barcode'],
                'product_type' => 'simple',
                'track_inventory' => true,
                'cost_price' => $data['cost_price'],
                'sale_price' => $data['sale_price'],
                'status' => 'active',
                'description' => $data['description'],
            ]);
        }

        return $products;
    }

    /**
     * @param  array<string, User>  $users
     * @param  array<string, Warehouse>  $warehouses
     * @param  array<string, Customer>  $customers
     * @param  array<string, Supplier>  $suppliers
     * @param  array<string, Product>  $products
     * @return array<string, mixed>
     */
    protected function seedTransactions(
        Business $business,
        array $users,
        array $warehouses,
        array $customers,
        array $suppliers,
        array $products
    ): array {
        $purchaseMainDate = CarbonImmutable::now()->subDays(10)->setTime(9, 0);
        $purchaseOnlineDate = CarbonImmutable::now()->subDays(7)->setTime(10, 30);
        $orderDate = CarbonImmutable::now()->subDays(2)->setTime(14, 0);
        $adjustmentDate = CarbonImmutable::now()->subDay()->setTime(18, 15);

        $stockInMain = StockIn::query()->create([
            'business_id' => $business->id,
            'warehouse_id' => $warehouses['main']->id,
            'supplier_id' => $suppliers['smart']->id,
            'created_by' => $users['manager']->id,
            'stock_in_no' => 'SI-0001',
            'reference_no' => 'PO-0001',
            'stock_in_type' => 'purchase',
            'stock_in_date' => $purchaseMainDate,
            'status' => 'confirmed',
            'subtotal' => 16450000,
            'discount_amount' => 450000,
            'total_amount' => 16000000,
            'note' => 'Nhap lo hang chinh cho kho tong',
        ]);

        $this->createStockInItems($stockInMain, [
            [$products['cable'], 100, 25000],
            [$products['charger'], 40, 120000],
            [$products['powerbank'], 25, 210000],
            [$products['bubble-wrap'], 120, 15000],
            [$products['stand'], 60, 35000],
        ]);

        $stockInOnline = StockIn::query()->create([
            'business_id' => $business->id,
            'warehouse_id' => $warehouses['online']->id,
            'supplier_id' => $suppliers['packing']->id,
            'created_by' => $users['manager']->id,
            'stock_in_no' => 'SI-0002',
            'reference_no' => 'PO-0002',
            'stock_in_type' => 'purchase',
            'stock_in_date' => $purchaseOnlineDate,
            'status' => 'confirmed',
            'subtotal' => 2280000,
            'discount_amount' => 0,
            'total_amount' => 2280000,
            'note' => 'Bo sung hang cho kho online',
        ]);

        $this->createStockInItems($stockInOnline, [
            [$products['cable'], 20, 26000],
            [$products['charger'], 10, 122000],
            [$products['stand'], 15, 36000],
        ]);

        $order = Order::query()->create([
            'business_id' => $business->id,
            'warehouse_id' => $warehouses['online']->id,
            'customer_id' => $customers['linh']->id,
            'created_by' => $users['staff']->id,
            'order_no' => 'ORD-0001',
            'order_date' => $orderDate,
            'status' => 'completed',
            'payment_status' => 'paid',
            'subtotal' => 801000,
            'discount_amount' => 21000,
            'shipping_amount' => 30000,
            'total_amount' => 810000,
            'paid_amount' => 810000,
            'note' => 'Don Facebook chot nhanh trong ngay',
        ]);

        $this->createOrderItems($order, [
            [$products['cable'], 3, 49000, 0],
            [$products['charger'], 2, 189000, 12000],
            [$products['stand'], 4, 69000, 9000],
        ]);

        $stockOut = StockOut::query()->create([
            'business_id' => $business->id,
            'warehouse_id' => $warehouses['online']->id,
            'order_id' => $order->id,
            'customer_id' => $customers['linh']->id,
            'created_by' => $users['staff']->id,
            'stock_out_no' => 'SO-0001',
            'reference_no' => $order->order_no,
            'stock_out_type' => 'sale',
            'stock_out_date' => $orderDate->addHour(),
            'status' => 'confirmed',
            'subtotal' => 801000,
            'total_amount' => 801000,
            'note' => 'Xuat kho cho don ORD-0001',
        ]);

        $this->createStockOutItems($stockOut, [
            [$products['cable'], 3, 49000],
            [$products['charger'], 2, 189000],
            [$products['stand'], 4, 69000],
        ]);

        $adjustment = StockAdjustment::query()->create([
            'business_id' => $business->id,
            'warehouse_id' => $warehouses['main']->id,
            'created_by' => $users['manager']->id,
            'adjustment_no' => 'ADJ-0001',
            'adjustment_date' => $adjustmentDate,
            'reason' => 'Kiem kho cuoi ngay',
            'status' => 'confirmed',
            'note' => 'Lech xop hoi va tim thay them 1 pin du phong',
        ]);

        $this->createStockAdjustmentItems($adjustment, [
            [$products['bubble-wrap'], 120, 115, 15000, 'Xop hoi mat do dem sai'],
            [$products['powerbank'], 25, 26, 210000, 'Tim thay them 1 san pham trong ke'],
        ]);

        $paymentIn = Payment::query()->create([
            'business_id' => $business->id,
            'order_id' => $order->id,
            'stock_in_id' => null,
            'customer_id' => $customers['linh']->id,
            'supplier_id' => null,
            'created_by' => $users['staff']->id,
            'payment_no' => 'PAY-IN-0001',
            'direction' => 'in',
            'method' => 'bank_transfer',
            'status' => 'paid',
            'amount' => 810000,
            'payment_date' => $orderDate->addHours(2),
            'reference_no' => $order->order_no,
            'note' => 'Khach chuyen khoan du tien',
        ]);

        $paymentOutMain = Payment::query()->create([
            'business_id' => $business->id,
            'order_id' => null,
            'stock_in_id' => $stockInMain->id,
            'customer_id' => null,
            'supplier_id' => $suppliers['smart']->id,
            'created_by' => $users['owner']->id,
            'payment_no' => 'PAY-OUT-0001',
            'direction' => 'out',
            'method' => 'bank_transfer',
            'status' => 'paid',
            'amount' => 10000000,
            'payment_date' => $purchaseMainDate->addHours(3),
            'reference_no' => $stockInMain->stock_in_no,
            'note' => 'Thanh toan dot 1 cho nha cung cap Smart Accessories',
        ]);

        $paymentOutOnline = Payment::query()->create([
            'business_id' => $business->id,
            'order_id' => null,
            'stock_in_id' => $stockInOnline->id,
            'customer_id' => null,
            'supplier_id' => $suppliers['packing']->id,
            'created_by' => $users['owner']->id,
            'payment_no' => 'PAY-OUT-0002',
            'direction' => 'out',
            'method' => 'cash',
            'status' => 'paid',
            'amount' => 2280000,
            'payment_date' => $purchaseOnlineDate->addHours(1),
            'reference_no' => $stockInOnline->stock_in_no,
            'note' => 'Thanh toan du cho don nhap kho online',
        ]);

        return [
            'stock_in' => [$stockInMain, $stockInOnline],
            'order' => $order,
            'stock_out' => $stockOut,
            'adjustment' => $adjustment,
            'payments' => [$paymentIn, $paymentOutMain, $paymentOutOnline],
        ];
    }

    protected function createStockInItems(StockIn $stockIn, array $items): void
    {
        foreach ($items as [$product, $quantity, $unitCost]) {
            StockInItem::query()->create([
                'business_id' => $stockIn->business_id,
                'stock_in_id' => $stockIn->id,
                'product_id' => $product->id,
                'product_sku' => $product->sku,
                'product_name' => $product->name,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'line_total' => $quantity * $unitCost,
            ]);
        }
    }

    protected function createOrderItems(Order $order, array $items): void
    {
        foreach ($items as [$product, $quantity, $unitPrice, $discountAmount]) {
            OrderItem::query()->create([
                'business_id' => $order->business_id,
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_sku' => $product->sku,
                'product_name' => $product->name,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount_amount' => $discountAmount,
                'line_total' => ($quantity * $unitPrice) - $discountAmount,
            ]);
        }
    }

    protected function createStockOutItems(StockOut $stockOut, array $items): void
    {
        foreach ($items as [$product, $quantity, $unitPrice]) {
            StockOutItem::query()->create([
                'business_id' => $stockOut->business_id,
                'stock_out_id' => $stockOut->id,
                'product_id' => $product->id,
                'product_sku' => $product->sku,
                'product_name' => $product->name,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $quantity * $unitPrice,
            ]);
        }
    }

    protected function createStockAdjustmentItems(StockAdjustment $adjustment, array $items): void
    {
        foreach ($items as [$product, $expectedQty, $countedQty, $unitCost, $note]) {
            $differenceQty = $countedQty - $expectedQty;

            StockAdjustmentItem::query()->create([
                'business_id' => $adjustment->business_id,
                'stock_adjustment_id' => $adjustment->id,
                'product_id' => $product->id,
                'product_sku' => $product->sku,
                'product_name' => $product->name,
                'expected_qty' => $expectedQty,
                'counted_qty' => $countedQty,
                'difference_qty' => $differenceQty,
                'unit_cost' => $unitCost,
                'line_total' => $differenceQty * $unitCost,
                'note' => $note,
            ]);
        }
    }

    /**
     * @param  array<string, User>  $users
     * @param  array<string, Warehouse>  $warehouses
     * @param  array<string, Product>  $products
     * @param  array<string, mixed>  $documents
     */
    protected function seedInventoryReadModels(
        Business $business,
        array $users,
        array $warehouses,
        array $products,
        array $documents
    ): void {
        $stockBalances = [];

        foreach ($documents['stock_in'] as $stockIn) {
            foreach ($stockIn->items as $item) {
                InventoryMovement::query()->create([
                    'business_id' => $business->id,
                    'warehouse_id' => $stockIn->warehouse_id,
                    'product_id' => $item->product_id,
                    'movement_type' => 'stock_in',
                    'source_type' => 'stock_in',
                    'source_id' => $stockIn->id,
                    'source_code' => $stockIn->stock_in_no,
                    'quantity_change' => $item->quantity,
                    'unit_cost' => $item->unit_cost,
                    'total_cost' => $item->line_total,
                    'movement_date' => $stockIn->stock_in_date,
                    'note' => $stockIn->note,
                    'created_by' => $users['manager']->id,
                ]);

                $this->applyStockBalance($stockBalances, $business->id, $stockIn->warehouse_id, $item->product_id, (float) $item->quantity, (float) $item->unit_cost, $stockIn->stock_in_date);
            }
        }

        foreach ($documents['stock_out']->items as $item) {
            InventoryMovement::query()->create([
                'business_id' => $business->id,
                'warehouse_id' => $documents['stock_out']->warehouse_id,
                'product_id' => $item->product_id,
                'movement_type' => 'stock_out',
                'source_type' => 'stock_out',
                'source_id' => $documents['stock_out']->id,
                'source_code' => $documents['stock_out']->stock_out_no,
                'quantity_change' => -1 * $item->quantity,
                'unit_cost' => $item->product->cost_price,
                'total_cost' => -1 * $item->quantity * $item->product->cost_price,
                'movement_date' => $documents['stock_out']->stock_out_date,
                'note' => $documents['stock_out']->note,
                'created_by' => $users['staff']->id,
            ]);

            $this->applyStockBalance(
                $stockBalances,
                $business->id,
                $documents['stock_out']->warehouse_id,
                $item->product_id,
                -1 * (float) $item->quantity,
                (float) $item->product->cost_price,
                $documents['stock_out']->stock_out_date
            );
        }

        foreach ($documents['adjustment']->items as $item) {
            $movementType = $item->difference_qty >= 0 ? 'adjustment_in' : 'adjustment_out';

            InventoryMovement::query()->create([
                'business_id' => $business->id,
                'warehouse_id' => $documents['adjustment']->warehouse_id,
                'product_id' => $item->product_id,
                'movement_type' => $movementType,
                'source_type' => 'stock_adjustment',
                'source_id' => $documents['adjustment']->id,
                'source_code' => $documents['adjustment']->adjustment_no,
                'quantity_change' => $item->difference_qty,
                'unit_cost' => $item->unit_cost,
                'total_cost' => $item->line_total,
                'movement_date' => $documents['adjustment']->adjustment_date,
                'note' => $item->note,
                'created_by' => $users['manager']->id,
            ]);

            $this->applyStockBalance(
                $stockBalances,
                $business->id,
                $documents['adjustment']->warehouse_id,
                $item->product_id,
                (float) $item->difference_qty,
                (float) $item->unit_cost,
                $documents['adjustment']->adjustment_date
            );
        }

        foreach ($stockBalances as $balance) {
            CurrentStock::query()->create($balance);
        }
    }

    protected function applyStockBalance(
        array &$stockBalances,
        int $businessId,
        int $warehouseId,
        int $productId,
        float $quantityDelta,
        float $unitCost,
        mixed $movementDate
    ): void {
        $key = implode(':', [$businessId, $warehouseId, $productId]);

        if (! isset($stockBalances[$key])) {
            $stockBalances[$key] = [
                'business_id' => $businessId,
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'quantity_on_hand' => 0,
                'avg_unit_cost' => 0,
                'stock_value' => 0,
                'last_movement_at' => $movementDate,
            ];
        }

        $currentQty = (float) $stockBalances[$key]['quantity_on_hand'];
        $currentValue = (float) $stockBalances[$key]['stock_value'];

        if ($quantityDelta > 0) {
            $currentQty += $quantityDelta;
            $currentValue += $quantityDelta * $unitCost;
        } else {
            $avgCost = $currentQty > 0 ? $currentValue / $currentQty : $unitCost;
            $currentQty += $quantityDelta;
            $currentValue += $quantityDelta * $avgCost;
        }

        $stockBalances[$key]['quantity_on_hand'] = round($currentQty, 3);
        $stockBalances[$key]['stock_value'] = round(max($currentValue, 0), 2);
        $stockBalances[$key]['avg_unit_cost'] = $currentQty > 0
            ? round($stockBalances[$key]['stock_value'] / $currentQty, 2)
            : 0;
        $stockBalances[$key]['last_movement_at'] = $movementDate;
    }
}
