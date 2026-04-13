<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\BusinessModule;
use App\Models\BusinessSequence;
use App\Models\BusinessUser;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder dữ liệu demo nền cho project sau khi đã loại bỏ các domain 5.4+.
 */
class MvpInventorySeeder extends Seeder
{
    public function run(): void
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

        BusinessSequence::query()->where('business_id', $business->id)->delete();
        BusinessModule::query()->where('business_id', $business->id)->delete();
        BusinessUser::query()->where('business_id', $business->id)->delete();
        Product::withTrashed()->where('business_id', $business->id)->forceDelete();
        Category::withTrashed()->where('business_id', $business->id)->forceDelete();
        Supplier::withTrashed()->where('business_id', $business->id)->forceDelete();
        Customer::withTrashed()->where('business_id', $business->id)->forceDelete();
        Warehouse::withTrashed()->where('business_id', $business->id)->forceDelete();
        Unit::withTrashed()->where('business_id', $business->id)->forceDelete();

        $owner = $this->upsertUser('Demo Owner', 'owner@demo-store.local', '0901000100');
        $manager = $this->upsertUser('Demo Manager', 'manager@demo-store.local', '0901000101');
        $staff = $this->upsertUser('Demo Staff', 'staff@demo-store.local', '0901000102');

        BusinessUser::query()->create([
            'business_id' => $business->id,
            'user_id' => $owner->id,
            'role' => 'owner',
            'status' => 'active',
            'is_owner' => true,
            'joined_at' => now()->subMonths(2),
        ]);

        BusinessUser::query()->create([
            'business_id' => $business->id,
            'user_id' => $manager->id,
            'role' => 'manager',
            'status' => 'active',
            'is_owner' => false,
            'joined_at' => now()->subMonths(2)->addDays(3),
        ]);

        BusinessUser::query()->create([
            'business_id' => $business->id,
            'user_id' => $staff->id,
            'role' => 'staff',
            'status' => 'active',
            'is_owner' => false,
            'joined_at' => now()->subMonths(2)->addDays(7),
        ]);

        foreach (['products', 'inventory', 'customers', 'suppliers'] as $moduleCode) {
            BusinessModule::query()->create([
                'business_id' => $business->id,
                'module_code' => $moduleCode,
                'status' => 'active',
                'starts_at' => now()->subMonths(2),
                'ends_at' => null,
            ]);
        }

        $piece = Unit::query()->create([
            'business_id' => $business->id,
            'name' => 'Cai',
            'is_active' => true,
        ]);

        $box = Unit::query()->create([
            'business_id' => $business->id,
            'name' => 'Hop',
            'is_active' => true,
        ]);

        Warehouse::query()->create([
            'business_id' => $business->id,
            'name' => 'Kho Trung Tam',
            'address' => '123 Nguyen Trai, Ho Chi Minh',
            'is_active' => true,
        ]);

        Warehouse::query()->create([
            'business_id' => $business->id,
            'name' => 'Kho Ha Noi',
            'address' => '45 Cau Giay, Ha Noi',
            'is_active' => true,
        ]);

        $accessories = Category::query()->create([
            'business_id' => $business->id,
            'name' => 'Phu kien',
            'is_active' => true,
        ]);

        $devices = Category::query()->create([
            'business_id' => $business->id,
            'name' => 'Thiet bi',
            'is_active' => true,
        ]);

        Customer::query()->create([
            'business_id' => $business->id,
            'name' => 'Nguyen Thi Ha',
            'phone' => '0901234567',
            'email' => 'ha@example.com',
            'is_active' => true,
        ]);

        Customer::query()->create([
            'business_id' => $business->id,
            'name' => 'Tran Minh Quan',
            'phone' => '0902345678',
            'email' => 'quan@example.com',
            'is_active' => true,
        ]);

        Supplier::query()->create([
            'business_id' => $business->id,
            'name' => 'Nha cung cap dien tu',
            'contact_name' => 'Le Van A',
            'phone' => '02838889999',
            'email' => 'sales@ncc-dientu.local',
            'is_active' => true,
        ]);

        Supplier::query()->create([
            'business_id' => $business->id,
            'name' => 'Nha cung cap phu kien',
            'contact_name' => 'Pham Thi B',
            'phone' => '02837778888',
            'email' => 'sales@ncc-phukien.local',
            'is_active' => true,
        ]);

        Product::query()->create([
            'business_id' => $business->id,
            'unit_id' => $piece->id,
            'category_id' => $accessories->id,
            'sku' => 'DEMO-SAC-0001',
            'name' => 'Cu sac nhanh 20W',
            'barcode' => '8938501000001',
            'product_type' => 'simple',
            'track_inventory' => true,
            'cost_price' => 85000,
            'sale_price' => 120000,
            'is_active' => true,
            'description' => 'San pham demo cho catalog.',
        ]);

        Product::query()->create([
            'business_id' => $business->id,
            'unit_id' => $piece->id,
            'category_id' => $accessories->id,
            'sku' => 'DEMO-CAP-0002',
            'name' => 'Cap Type C 1m',
            'barcode' => '8938501000002',
            'product_type' => 'simple',
            'track_inventory' => true,
            'cost_price' => 25000,
            'sale_price' => 49000,
            'is_active' => true,
            'description' => 'San pham demo cho catalog.',
        ]);

        Product::query()->create([
            'business_id' => $business->id,
            'unit_id' => $box->id,
            'category_id' => $devices->id,
            'sku' => 'DEMO-LOA-0003',
            'name' => 'Loa bluetooth mini',
            'barcode' => '8938501000003',
            'product_type' => 'simple',
            'track_inventory' => true,
            'cost_price' => 320000,
            'sale_price' => 450000,
            'is_active' => true,
            'description' => 'San pham demo cho catalog.',
        ]);
    }

    protected function upsertUser(string $name, string $email, string $phone): User
    {
        $user = User::withTrashed()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'phone' => $phone,
                'password' => Hash::make('password'),
                'is_active' => true,
                'last_login_at' => now()->subDay(),
            ]
        );

        if ($user->trashed()) {
            $user->restore();
        }

        return $user;
    }
}
