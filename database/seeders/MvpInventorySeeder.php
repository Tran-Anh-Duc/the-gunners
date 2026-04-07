<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\BusinessModule;
use App\Models\BusinessSequence;
use App\Models\BusinessUser;
use App\Models\Category;
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
use App\Support\NameSlug;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeder dữ liệu demo cho MVP quản lý kho.
 *
 * Bộ dữ liệu này mô phỏng một shop nhỏ đã có:
 * - business, user và membership;
 * - danh mục hàng hóa, kho, khách hàng, nhà cung cấp;
 * - chứng từ nhập, xuất, kiểm kho, thanh toán;
 * - read model tồn kho để UI có thể dùng ngay.
 */
class MvpInventorySeeder extends Seeder
{
    /**
     * Gieo toàn bộ dữ liệu demo trong một transaction.
     *
     * Cách làm này giúp seed hoặc thành công trọn vẹn,
     * hoặc rollback toàn bộ nếu có lỗi giữa chừng.
     */
    public function run(): void
    {
        // Với DB remote như Aiven, một transaction quá dài sẽ làm seed rất chậm.
        $business = $this->seedBusiness();
        $users = $this->seedUsers();

        $this->resetDemoBusinessData($business->id);
        $this->seedMemberships($business, $users);
        $this->seedModules($business);

        $units = $this->seedUnits($business);
        $warehouses = $this->seedWarehouses($business);
        $customers = $this->seedCustomers($business);
        $suppliers = $this->seedSuppliers($business);
        $categories = $this->seedCategories($business);
        $products = $this->seedProducts($business, $units, $categories);

        $documents = $this->seedTransactions($business, $users, $warehouses, $customers, $suppliers, $products);
        $this->seedInventoryReadModels($business, $users, $warehouses, $products, $documents);
    }

    /**
     * Tạo hoặc làm mới business demo gốc của hệ thống.
     *
     * Business này đóng vai trò tenant mẫu cho toàn bộ dữ liệu seed phía sau.
     */
    protected function seedBusiness(): Business
    {
        $business = Business::withTrashed()->updateOrCreate(
            ['code' => 'demo-store'],
            $this->withNameSlug([
                'name' => 'Demo Store',
                'phone' => '0901000100',
                'email' => 'owner@demo-store.local',
                'address' => '123 Nguyen Trai, Ho Chi Minh',
                'plan_code' => 'starter',
                'status' => 'active',
                'currency_code' => 'VND',
                'timezone' => 'Asia/Ho_Chi_Minh',
            ])
        );

        if ($business->trashed()) {
            $business->restore();
        }

        return $business;
    }

    /**
     * @return array<string, User>
     *
     * Tạo bộ user demo theo ba vai trò chính:
     * - owner
     * - manager
     * - staff
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
            'staff2' => [
                'name' => 'Demo Staff 2',
                'email' => 'staff2@demo-store.local',
                'phone' => '0901000103',
                'is_active' => true,
            ],
            'support' => [
                'name' => 'Demo Support',
                'email' => 'support@demo-store.local',
                'phone' => '0901000104',
                'is_active' => true,
            ],
        ];

        foreach ($users as $key => $data) {
            $user = User::withTrashed()->updateOrCreate(
                ['email' => $data['email']],
                $this->withNameSlug([
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'password' => Hash::make('password'),
                    'is_active' => $data['is_active'],
                    'last_login_at' => CarbonImmutable::now()->subDay(),
                ])
            );

            if ($user->trashed()) {
                $user->restore();
            }

            $users[$key] = $user;
        }

        for ($index = 1; $index <= 45; $index++) {
            $key = sprintf('generated_%02d', $index);

            $user = User::withTrashed()->updateOrCreate(
                ['email' => sprintf('demo-user-%02d@demo-store.local', $index)],
                $this->withNameSlug([
                    'name' => sprintf('Demo User %02d', $index),
                    'phone' => sprintf('0912%06d', $index),
                    'password' => Hash::make('password'),
                    'is_active' => true,
                    'last_login_at' => CarbonImmutable::now()->subHours($index),
                ])
            );

            if ($user->trashed()) {
                $user->restore();
            }

            $users[$key] = $user;
        }

        return $users;
    }

    /**
     * Dọn sạch dữ liệu nghiệp vụ cũ của business demo trước khi seed lại.
     *
     * Thứ tự xóa được sắp theo quan hệ nghiệp vụ để tránh vướng khóa ngoại
     * và bảo đảm lần seed sau luôn cho ra bộ dữ liệu nhất quán.
     */
    protected function resetDemoBusinessData(int $businessId): void
    {
        // Xóa dữ liệu demo cũ theo đúng thứ tự nghiệp vụ để seed lại không bị trùng.
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
        Category::withTrashed()->where('business_id', $businessId)->forceDelete();
        Supplier::withTrashed()->where('business_id', $businessId)->forceDelete();
        Customer::withTrashed()->where('business_id', $businessId)->forceDelete();
        Warehouse::withTrashed()->where('business_id', $businessId)->forceDelete();
        Unit::withTrashed()->where('business_id', $businessId)->forceDelete();
        BusinessSequence::query()->where('business_id', $businessId)->delete();
        BusinessModule::query()->where('business_id', $businessId)->delete();
        BusinessUser::query()->where('business_id', $businessId)->delete();
    }

    /**
     * @param  array<string, User>  $users
     *
     * Gắn user demo vào business với các vai trò khác nhau.
     */
    protected function seedMemberships(Business $business, array $users): void
    {
        // Membership là lớp mang role và quyền truy cập theo business trong MVP.
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

        BusinessUser::query()->create([
            'business_id' => $business->id,
            'user_id' => $users['staff2']->id,
            'role' => 'staff',
            'status' => 'active',
            'is_owner' => false,
            'joined_at' => $joinedAt->addDays(10),
        ]);

        BusinessUser::query()->create([
            'business_id' => $business->id,
            'user_id' => $users['support']->id,
            'role' => 'manager',
            'status' => 'active',
            'is_owner' => false,
            'joined_at' => $joinedAt->addDays(14),
        ]);

        $generatedUsers = collect($users)
            ->except(['owner', 'manager', 'staff', 'staff2', 'support'])
            ->values();

        foreach ($generatedUsers as $index => $user) {
            BusinessUser::query()->create([
                'business_id' => $business->id,
                'user_id' => $user->id,
                'role' => ($index + 1) % 6 === 0 ? 'manager' : 'staff',
                'status' => 'active',
                'is_owner' => false,
                'joined_at' => $joinedAt->addDays(20 + $index),
            ]);
        }
    }

    /**
     * Bật bộ module mặc định cho business demo.
     *
     * Việc seed sẵn giúp UI có thể dựa vào `business_modules`
     * để hiển thị hoặc ẩn bớt tính năng ngay từ dữ liệu demo.
     */
    protected function seedModules(Business $business): void
    {
        // Đây là bộ module cơ bản mà UI có thể bật hoặc tắt theo gói.
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
     *
     * Tạo danh mục đơn vị tính cơ bản cho shop demo.
     */
    protected function seedUnits(Business $business): array
    {
        $units = [
            'pcs' => [
                'name' => 'Cai',
                'description' => 'Don vì ban le mặc định',
            ],
            'box' => [
                'name' => 'Hop',
                'description' => 'Dong gọi theo hop',
            ],
            'set' => [
                'name' => 'Bo',
                'description' => 'Don vì dùng cho combo san pham',
            ],
            'roll' => [
                'name' => 'Cuon',
                'description' => 'Don vì cho vat từ dong goi theo cuon',
            ],
        ];

        foreach ($units as $key => $data) {
            $units[$key] = Unit::query()->create([
                'business_id' => $business->id,
                'name' => $data['name'],
                'name_slug' => NameSlug::from($data['name']),
                'description' => $data['description'],
                'is_active' => true,
            ]);
        }

        for ($index = 1; $index <= 16; $index++) {
            $key = sprintf('generated_%02d', $index);

            $units[$key] = Unit::query()->create([
                'business_id' => $business->id,
                'name' => sprintf('Don vi %02d', $index),
                'name_slug' => NameSlug::from(sprintf('Don vi %02d', $index)),
                'description' => sprintf('Don vi phu de test filter va pagination %02d', $index),
                'is_active' => true,
            ]);
        }

        return $units;
    }

    /**
     * @return array<string, Warehouse>
     *
     * Tạo các kho mẫu để mô phỏng bài toán tồn kho nhiều địa điểm.
     */
    protected function seedWarehouses(Business $business): array
    {
        $warehouses = [
            'main' => [
                'name' => 'Kho chinh',
                'address' => 'Tang tret - 123 Nguyen Trai',
            ],
            'online' => [
                'name' => 'Kho đơn online',
                'address' => 'Tang 2 - 123 Nguyen Trai',
            ],
            'showroom' => [
                'name' => 'Kho showroom',
                'address' => 'Mat tien - 123 Nguyen Trai',
            ],
        ];

        foreach ($warehouses as $key => $data) {
            $warehouses[$key] = Warehouse::query()->create([
                'business_id' => $business->id,
                'name' => $data['name'],
                'name_slug' => NameSlug::from($data['name']),
                'address' => $data['address'],
                'is_active' => true,
            ]);
        }

        for ($index = 1; $index <= 7; $index++) {
            $key = sprintf('branch_%02d', $index);

            $warehouses[$key] = Warehouse::query()->create([
                'business_id' => $business->id,
                'name' => sprintf('Kho chi nhanh %02d', $index),
                'name_slug' => NameSlug::from(sprintf('Kho chi nhanh %02d', $index)),
                'address' => sprintf('%d Le Loi, Ho Chi Minh', 200 + $index),
                'is_active' => true,
            ]);
        }

        return $warehouses;
    }

    /**
     * @return array<string, Category>
     */
    protected function seedCategories(Business $business): array
    {
        $categories = [
            'charging' => [
                'name' => 'Sac va nguon',
                'description' => 'Phu kien sac, nguon, adapter',
            ],
            'audio' => [
                'name' => 'Am thanh',
                'description' => 'Tai nghe, loa va phu kien am thanh',
            ],
            'protection' => [
                'name' => 'Bao ve',
                'description' => 'Op lung, kinh va phu kien bao ve',
            ],
            'stand' => [
                'name' => 'Gia do',
                'description' => 'Gia do va phu kien trung bay',
            ],
            'packing' => [
                'name' => 'Dong goi',
                'description' => 'Vat tu dong goi va giao hang',
            ],
        ];

        foreach ($categories as $key => $data) {
            $categories[$key] = Category::query()->create([
                'business_id' => $business->id,
                'name' => $data['name'],
                'name_slug' => NameSlug::from($data['name']),
                'description' => $data['description'],
                'is_active' => true,
            ]);
        }

        return $categories;
    }

    /**
     * @return array<string, Customer>
     *
     * Tạo một vài khách hàng demo với nguồn mua khác nhau
     * để dữ liệu list và chứng từ bán hàng sinh động hơn.
     */
    protected function seedCustomers(Business $business): array
    {
        $customers = [
            'linh' => [
                'name' => 'Nguyen Thi Linh',
                'phone' => '0902333444',
                'email' => 'linh.customer@example.com',
                'address' => 'Go Vap, Ho Chi Minh',
                'note' => 'Khach mua từ Facebook',
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
                'note' => 'Khach mua lại nhieu lan',
            ],
            'hieu' => [
                'name' => 'Le Gia Hieu',
                'phone' => '0902777666',
                'email' => 'hieu.customer@example.com',
                'address' => 'Binh Thanh, Ho Chi Minh',
                'note' => 'Khach lay hang tai showroom',
            ],
            'thao' => [
                'name' => 'Vo Minh Thao',
                'phone' => '0902444555',
                'email' => 'thao.customer@example.com',
                'address' => 'Tan Binh, Ho Chi Minh',
                'note' => 'Khach mua combo va phu kien',
            ],
            'corporate' => [
                'name' => 'ABC Office Supplies',
                'phone' => '02839990001',
                'email' => 'purchasing@abc-office.local',
                'address' => 'District 1, Ho Chi Minh',
                'note' => 'Khach doanh nghiep dat hang dinh ky',
            ],
        ];

        foreach ($customers as $key => $data) {
            $customers[$key] = Customer::query()->create([
                'business_id' => $business->id,
                'name' => $data['name'],
                'name_slug' => NameSlug::from($data['name']),
                'phone' => $data['phone'],
                'email' => $data['email'],
                'address' => $data['address'],
                'note' => $data['note'],
                'is_active' => true,
            ]);
        }

        for ($index = 1; $index <= 54; $index++) {
            $key = sprintf('generated_%02d', $index);

            $customers[$key] = Customer::query()->create([
                'business_id' => $business->id,
                'name' => sprintf('Khach Demo %02d', $index),
                'name_slug' => NameSlug::from(sprintf('Khach Demo %02d', $index)),
                'phone' => sprintf('0933%06d', $index),
                'email' => sprintf('customer%02d@demo-store.local', $index),
                'address' => sprintf('%d Tran Hung Dao, Ho Chi Minh', 50 + $index),
                'note' => sprintf('Khach duoc seed de test danh sach va bo loc #%02d', $index),
                'is_active' => true,
            ]);
        }

        return $customers;
    }

    /**
     * @return array<string, Supplier>
     *
     * Tạo nhà cung cấp mẫu cho luồng nhập hàng và thanh toán ra.
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
                'note' => 'Nhà cùng cấp phu kien dien thoai',
            ],
            'packing' => [
                'name' => 'Packing Hub',
                'contact_name' => 'Vo Mai',
                'phone' => '02837775555',
                'email' => 'hello@packing-hub.local',
                'address' => 'Tan Phu, Ho Chi Minh',
                'note' => 'Nhà cùng cấp vat từ dòng goi',
            ],
            'mobileplus' => [
                'name' => 'Mobile Plus Distribution',
                'contact_name' => 'Nguyen Tuan',
                'phone' => '02836668888',
                'email' => 'sales@mobile-plus.local',
                'address' => 'District 10, Ho Chi Minh',
                'note' => 'Nhà cùng cấp linh kien va phu kien trung cap',
            ],
            'gadgetworld' => [
                'name' => 'Gadget World',
                'contact_name' => 'Tran Y Nhi',
                'phone' => '02835557777',
                'email' => 'biz@gadget-world.local',
                'address' => 'Thu Duc, Ho Chi Minh',
                'note' => 'Nhà cùng cấp mat kinh va phu kien ban le',
            ],
        ];

        foreach ($suppliers as $key => $data) {
            $suppliers[$key] = Supplier::query()->create([
                'business_id' => $business->id,
                'name' => $data['name'],
                'name_slug' => NameSlug::from($data['name']),
                'contact_name' => $data['contact_name'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'address' => $data['address'],
                'note' => $data['note'],
                'is_active' => true,
            ]);
        }

        for ($index = 1; $index <= 16; $index++) {
            $key = sprintf('generated_%02d', $index);

            $suppliers[$key] = Supplier::query()->create([
                'business_id' => $business->id,
                'name' => sprintf('Supplier Demo %02d', $index),
                'name_slug' => NameSlug::from(sprintf('Supplier Demo %02d', $index)),
                'contact_name' => sprintf('Contact %02d', $index),
                'phone' => sprintf('0283%06d', 500000 + $index),
                'email' => sprintf('supplier%02d@demo-store.local', $index),
                'address' => sprintf('%d Nguyen Van Cu, Ho Chi Minh', 80 + $index),
                'note' => sprintf('Nha cung cap duoc seed de test danh sach va bo loc #%02d', $index),
                'is_active' => true,
            ]);
        }

        return $suppliers;
    }

    /**
     * @param  array<string, Unit>  $units
     * @return array<string, Product>
     *
     * Tạo danh mục sản phẩm demo.
     * Dữ liệu được chọn để có cả hàng bán chính và vật tư đóng gói.
     */
    protected function seedProducts(Business $business, array $units, array $categories): array
    {
        $products = [
            'cable' => [
                'sku' => 'SKU-CABLE-1M',
                'name' => 'Cặp sac Type-C 1m',
                'barcode' => '8938501000011',
                'cost_price' => 25000,
                'sale_price' => 49000,
                'description' => 'Day cặp sac thong dùng cho shop online',
            ],
            'charger' => [
                'sku' => 'SKU-CHARGER-20W',
                'name' => 'Cu sac nhanh 20W',
                'barcode' => '8938501000012',
                'cost_price' => 120000,
                'sale_price' => 189000,
                'description' => 'Cu sac nhanh dòng chu lúc ban chay',
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
                'name' => 'Xop hoi dòng hang 50m',
                'barcode' => '8938501000014',
                'cost_price' => 15000,
                'sale_price' => 29000,
                'description' => 'Vat từ dòng gọi cho đơn hàng',
            ],
            'stand' => [
                'sku' => 'SKU-STAND-ALU',
                'name' => 'Gia do dien thoai nhom',
                'barcode' => '8938501000015',
                'cost_price' => 35000,
                'sale_price' => 69000,
                'description' => 'Sản phẩm them để upsell',
            ],
            'case' => [
                'sku' => 'SKU-CASE-TPU',
                'name' => 'Op lung TPU trong',
                'barcode' => '8938501000016',
                'cost_price' => 18000,
                'sale_price' => 39000,
                'description' => 'Op lung bán chay cho don online va showroom',
            ],
            'earphone' => [
                'sku' => 'SKU-EAR-TWS',
                'name' => 'Tai nghe TWS co ban',
                'barcode' => '8938501000017',
                'cost_price' => 95000,
                'sale_price' => 159000,
                'description' => 'Tai nghe khong day phan khuc pho thong',
            ],
            'glass' => [
                'sku' => 'SKU-GLASS-2.5D',
                'name' => 'Kinh cuong luc 2.5D',
                'barcode' => '8938501000018',
                'cost_price' => 10000,
                'sale_price' => 29000,
                'description' => 'Phu kien gia re de ban kem dien thoai',
            ],
            'adapter' => [
                'sku' => 'SKU-ADAPTER-C2A',
                'name' => 'Dau chuyen Type-C sang USB-A',
                'barcode' => '8938501000019',
                'cost_price' => 45000,
                'sale_price' => 89000,
                'description' => 'Dau chuyen dung cho laptop va phu kien',
            ],
            'shipping-box' => [
                'sku' => 'SKU-BOX-SMALL',
                'name' => 'Hop carton nho',
                'barcode' => '8938501000020',
                'cost_price' => 8000,
                'sale_price' => 16000,
                'description' => 'Vat tu dong goi cho don hang giao nhanh',
            ],
        ];

        foreach ($products as $key => $data) {
            $unitId = match ($key) {
                'bubble-wrap' => $units['roll']->id,
                'shipping-box' => $units['box']->id,
                default => $units['pcs']->id,
            };

            $categoryId = match ($key) {
                'charger', 'powerbank', 'adapter' => $categories['charging']->id,
                'earphone' => $categories['audio']->id,
                'case', 'glass' => $categories['protection']->id,
                'stand' => $categories['stand']->id,
                'bubble-wrap', 'shipping-box' => $categories['packing']->id,
                default => null,
            };

            $products[$key] = Product::query()->create([
                'business_id' => $business->id,
                'unit_id' => $unitId,
                'category_id' => $categoryId,
                'sku' => $data['sku'],
                'name' => $data['name'],
                'name_slug' => NameSlug::from($data['name']),
                'barcode' => $data['barcode'],
                'product_type' => 'simple',
                'track_inventory' => true,
                'cost_price' => $data['cost_price'],
                'sale_price' => $data['sale_price'],
                'status' => 'active',
                'description' => $data['description'],
            ]);
        }

        $unitPool = array_values($units);
        $categoryPool = array_values($categories);

        for ($index = 1; $index <= 90; $index++) {
            $key = sprintf('generated_%03d', $index);
            $unit = $unitPool[$index % count($unitPool)];
            $category = $categoryPool[$index % count($categoryPool)];
            $costPrice = 12000 + ($index * 1700);
            $salePrice = $costPrice + 12000 + (($index % 5) * 3000);

            $products[$key] = Product::query()->create([
                'business_id' => $business->id,
                'unit_id' => $unit->id,
                'category_id' => $category->id,
                'sku' => sprintf('SKU-DEMO-%03d', $index),
                'name' => sprintf('San pham demo %03d', $index),
                'name_slug' => NameSlug::from(sprintf('San pham demo %03d', $index)),
                'barcode' => sprintf('8938502%06d', $index),
                'product_type' => 'simple',
                'track_inventory' => true,
                'cost_price' => $costPrice,
                'sale_price' => $salePrice,
                'status' => $index % 12 === 0 ? 'inactive' : 'active',
                'description' => sprintf('San pham duoc seed de test goi API, paging va bo loc #%03d', $index),
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
     *
     * Tạo bộ chứng từ mẫu để mô phỏng một luồng nghiệp vụ hoàn chỉnh:
     * - nhập kho;
     * - bán hàng;
     * - xuất kho;
     * - kiểm kho;
     * - thu và chi thanh toán.
     */
    protected function seedTransactions(
        Business $business,
        array $users,
        array $warehouses,
        array $customers,
        array $suppliers,
        array $products
    ): array {
        // Các mốc thời gian được tách rõ để lịch sử chứng từ và ledger nhìn tự nhiên hơn.
        $purchaseMainDate = CarbonImmutable::now()->subDays(10)->setTime(9, 0);
        $purchaseOnlineDate = CarbonImmutable::now()->subDays(7)->setTime(10, 30);
        $showroomPurchaseDate = CarbonImmutable::now()->subDays(5)->setTime(11, 45);
        $draftStockInDate = CarbonImmutable::now()->subDays(3)->setTime(16, 20);
        $orderDate = CarbonImmutable::now()->subDays(2)->setTime(14, 0);
        $secondOrderDate = CarbonImmutable::now()->subDay()->setTime(11, 15);
        $draftOrderDate = CarbonImmutable::now()->subHours(18);
        $adjustmentDate = CarbonImmutable::now()->subDay()->setTime(18, 15);
        $secondAdjustmentDate = CarbonImmutable::now()->subHours(10);

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

        $stockInShowroom = StockIn::query()->create([
            'business_id' => $business->id,
            'warehouse_id' => $warehouses['showroom']->id,
            'supplier_id' => $suppliers['mobileplus']->id,
            'created_by' => $users['support']->id,
            'stock_in_no' => 'SI-0003',
            'reference_no' => 'PO-0003',
            'stock_in_type' => 'purchase',
            'stock_in_date' => $showroomPurchaseDate,
            'status' => 'confirmed',
            'subtotal' => 8625000,
            'discount_amount' => 125000,
            'total_amount' => 8500000,
            'note' => 'Nhap lo hang bo sung cho showroom',
        ]);

        $this->createStockInItems($stockInShowroom, [
            [$products['case'], 80, 18000],
            [$products['earphone'], 35, 95000],
            [$products['glass'], 150, 10000],
            [$products['adapter'], 40, 45000],
            [$products['shipping-box'], 70, 8000],
        ]);

        $stockInDraft = StockIn::query()->create([
            'business_id' => $business->id,
            'warehouse_id' => $warehouses['main']->id,
            'supplier_id' => $suppliers['gadgetworld']->id,
            'created_by' => $users['support']->id,
            'stock_in_no' => 'SI-0004',
            'reference_no' => 'PO-0004',
            'stock_in_type' => 'return',
            'stock_in_date' => $draftStockInDate,
            'status' => 'draft',
            'subtotal' => 1260000,
            'discount_amount' => 0,
            'total_amount' => 1260000,
            'note' => 'Phieu nhap dang chờ xác nhận',
        ]);

        $this->createStockInItems($stockInDraft, [
            [$products['earphone'], 6, 96000],
            [$products['adapter'], 8, 47000],
            [$products['glass'], 30, 10400],
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

        $secondOrder = Order::query()->create([
            'business_id' => $business->id,
            'warehouse_id' => $warehouses['showroom']->id,
            'customer_id' => $customers['quang']->id,
            'created_by' => $users['support']->id,
            'order_no' => 'ORD-0002',
            'order_date' => $secondOrderDate,
            'status' => 'confirmed',
            'payment_status' => 'partial',
            'subtotal' => 1121000,
            'discount_amount' => 41000,
            'shipping_amount' => 25000,
            'total_amount' => 1105000,
            'paid_amount' => 400000,
            'note' => 'Don showroom con cong no mot phan',
        ]);

        $this->createOrderItems($secondOrder, [
            [$products['case'], 6, 39000, 14000],
            [$products['earphone'], 3, 159000, 17000],
            [$products['glass'], 8, 29000, 0],
            [$products['adapter'], 2, 89000, 10000],
        ]);

        $draftOrder = Order::query()->create([
            'business_id' => $business->id,
            'warehouse_id' => $warehouses['main']->id,
            'customer_id' => $customers['nhi']->id,
            'created_by' => $users['staff2']->id,
            'order_no' => 'ORD-0003',
            'order_date' => $draftOrderDate,
            'status' => 'draft',
            'payment_status' => 'unpaid',
            'subtotal' => 378000,
            'discount_amount' => 0,
            'shipping_amount' => 30000,
            'total_amount' => 408000,
            'paid_amount' => 0,
            'note' => 'Don nhap tu landing page, chua chot',
        ]);

        $this->createOrderItems($draftOrder, [
            [$products['powerbank'], 1, 329000, 0],
            [$products['cable'], 1, 49000, 0],
        ]);

        $cancelledOrder = Order::query()->create([
            'business_id' => $business->id,
            'warehouse_id' => $warehouses['showroom']->id,
            'customer_id' => $customers['corporate']->id,
            'created_by' => $users['manager']->id,
            'order_no' => 'ORD-0004',
            'order_date' => $secondOrderDate->subHours(4),
            'status' => 'cancelled',
            'payment_status' => 'unpaid',
            'subtotal' => 267000,
            'discount_amount' => 7000,
            'shipping_amount' => 20000,
            'total_amount' => 280000,
            'paid_amount' => 0,
            'note' => 'Don doanh nghiep huy do doi dia chi giao hang',
        ]);

        $this->createOrderItems($cancelledOrder, [
            [$products['charger'], 1, 189000, 0],
            [$products['case'], 2, 39000, 7000],
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
            'note' => 'Xuất kho cho đơn ORD-0001',
        ]);

        $this->createStockOutItems($stockOut, [
            [$products['cable'], 3, 49000],
            [$products['charger'], 2, 189000],
            [$products['stand'], 4, 69000],
        ]);

        $stockOutSecond = StockOut::query()->create([
            'business_id' => $business->id,
            'warehouse_id' => $warehouses['showroom']->id,
            'order_id' => $secondOrder->id,
            'customer_id' => $customers['quang']->id,
            'created_by' => $users['support']->id,
            'stock_out_no' => 'SO-0002',
            'reference_no' => $secondOrder->order_no,
            'stock_out_type' => 'sale',
            'stock_out_date' => $secondOrderDate->addHour(),
            'status' => 'confirmed',
            'subtotal' => 1121000,
            'total_amount' => 1121000,
            'note' => 'Xuất kho cho đơn ORD-0002',
        ]);

        $this->createStockOutItems($stockOutSecond, [
            [$products['case'], 6, 39000],
            [$products['earphone'], 3, 159000],
            [$products['glass'], 8, 29000],
            [$products['adapter'], 2, 89000],
        ]);

        $stockOutDraft = StockOut::query()->create([
            'business_id' => $business->id,
            'warehouse_id' => $warehouses['main']->id,
            'order_id' => $draftOrder->id,
            'customer_id' => $customers['nhi']->id,
            'created_by' => $users['staff2']->id,
            'stock_out_no' => 'SO-0003',
            'reference_no' => $draftOrder->order_no,
            'stock_out_type' => 'sale',
            'stock_out_date' => $draftOrderDate->addHour(),
            'status' => 'draft',
            'subtotal' => 378000,
            'total_amount' => 378000,
            'note' => 'Phiếu xuất nháp cho đơn ORD-0003',
        ]);

        $this->createStockOutItems($stockOutDraft, [
            [$products['powerbank'], 1, 329000],
            [$products['cable'], 1, 49000],
        ]);

        $adjustment = StockAdjustment::query()->create([
            'business_id' => $business->id,
            'warehouse_id' => $warehouses['main']->id,
            'created_by' => $users['manager']->id,
            'adjustment_no' => 'ADJ-0001',
            'adjustment_date' => $adjustmentDate,
            'reason' => 'Kiểm kho cuoi ngay',
            'status' => 'confirmed',
            'note' => 'Lech xop hoi va tim thay them 1 pin du phong',
        ]);

        $this->createStockAdjustmentItems($adjustment, [
            [$products['bubble-wrap'], 120, 115, 15000, 'Xop hoi mat do dem sai'],
            [$products['powerbank'], 25, 26, 210000, 'Tim thay them 1 sản phẩm trong ke'],
        ]);

        $secondAdjustment = StockAdjustment::query()->create([
            'business_id' => $business->id,
            'warehouse_id' => $warehouses['showroom']->id,
            'created_by' => $users['support']->id,
            'adjustment_no' => 'ADJ-0002',
            'adjustment_date' => $secondAdjustmentDate,
            'reason' => 'Kiểm kho showroom cuoi ca',
            'status' => 'confirmed',
            'note' => 'Lech nhe op lung va hop carton',
        ]);

        $this->createStockAdjustmentItems($secondAdjustment, [
            [$products['case'], 80, 78, 18000, 'Thieu 2 op lung do tra hang loi'],
            [$products['shipping-box'], 70, 74, 8000, 'Bo sung them tu kệ dong goi'],
        ]);

        $draftAdjustment = StockAdjustment::query()->create([
            'business_id' => $business->id,
            'warehouse_id' => $warehouses['main']->id,
            'created_by' => $users['manager']->id,
            'adjustment_no' => 'ADJ-0003',
            'adjustment_date' => CarbonImmutable::now()->subHours(2),
            'reason' => 'Phieu kiem kho cho duyet',
            'status' => 'draft',
            'note' => 'Dang dem lai phu kien kho tong',
        ]);

        $this->createStockAdjustmentItems($draftAdjustment, [
            [$products['charger'], 40, 39, 120000, 'Thieu tam thoi 1 cu sac khi dem nhanh'],
            [$products['glass'], 0, 6, 10000, 'Mat hang moi chua chot nhap kho'],
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
            'note' => 'Thanh toan dot 1 cho nhà cùng cấp Smart Accessories',
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
            'note' => 'Thanh toan du cho đơn nhập kho online',
        ]);

        $paymentInSecond = Payment::query()->create([
            'business_id' => $business->id,
            'order_id' => $secondOrder->id,
            'stock_in_id' => null,
            'customer_id' => $customers['quang']->id,
            'supplier_id' => null,
            'created_by' => $users['support']->id,
            'payment_no' => 'PAY-IN-0002',
            'direction' => 'in',
            'method' => 'cash',
            'status' => 'paid',
            'amount' => 400000,
            'payment_date' => $secondOrderDate->addHours(2),
            'reference_no' => $secondOrder->order_no,
            'note' => 'Khach dat coc tai showroom',
        ]);

        $paymentInDraft = Payment::query()->create([
            'business_id' => $business->id,
            'order_id' => $draftOrder->id,
            'stock_in_id' => null,
            'customer_id' => $customers['nhi']->id,
            'supplier_id' => null,
            'created_by' => $users['staff2']->id,
            'payment_no' => 'PAY-IN-0003',
            'direction' => 'in',
            'method' => 'e_wallet',
            'status' => 'pending',
            'amount' => 100000,
            'payment_date' => $draftOrderDate->addHour(),
            'reference_no' => $draftOrder->order_no,
            'note' => 'Cho xac nhan giao dich vi dien tu',
        ]);

        $paymentOutShowroom = Payment::query()->create([
            'business_id' => $business->id,
            'order_id' => null,
            'stock_in_id' => $stockInShowroom->id,
            'customer_id' => null,
            'supplier_id' => $suppliers['mobileplus']->id,
            'created_by' => $users['owner']->id,
            'payment_no' => 'PAY-OUT-0003',
            'direction' => 'out',
            'method' => 'bank_transfer',
            'status' => 'paid',
            'amount' => 5000000,
            'payment_date' => $showroomPurchaseDate->addHours(4),
            'reference_no' => $stockInShowroom->stock_in_no,
            'note' => 'Thanh toan dot 1 cho lo hang showroom',
        ]);

        $paymentOutShowroomPending = Payment::query()->create([
            'business_id' => $business->id,
            'order_id' => null,
            'stock_in_id' => $stockInShowroom->id,
            'customer_id' => null,
            'supplier_id' => $suppliers['mobileplus']->id,
            'created_by' => $users['owner']->id,
            'payment_no' => 'PAY-OUT-0004',
            'direction' => 'out',
            'method' => 'bank_transfer',
            'status' => 'pending',
            'amount' => 3500000,
            'payment_date' => $showroomPurchaseDate->addDays(1),
            'reference_no' => $stockInShowroom->stock_in_no,
            'note' => 'Cong no con lai cho nhà cùng cấp showroom',
        ]);

        $stockIns = [$stockInMain, $stockInOnline, $stockInShowroom, $stockInDraft];
        $orders = [$order, $secondOrder, $draftOrder, $cancelledOrder];
        $stockOuts = [$stockOut, $stockOutSecond, $stockOutDraft];
        $adjustments = [$adjustment, $secondAdjustment, $draftAdjustment];
        $payments = [
            $paymentIn,
            $paymentOutMain,
            $paymentOutOnline,
            $paymentInSecond,
            $paymentInDraft,
            $paymentOutShowroom,
            $paymentOutShowroomPending,
        ];

        $productPool = array_values($products);
        $warehousePool = array_values($warehouses);
        $customerPool = array_values($customers);
        $supplierPool = array_values($suppliers);
        $userPool = array_values($users);

        for ($index = 1; $index <= 18; $index++) {
            $warehouse = $warehousePool[$index % count($warehousePool)];
            $supplier = $supplierPool[$index % count($supplierPool)];
            $creator = $userPool[$index % count($userPool)];
            $status = $index <= 12 ? 'confirmed' : 'draft';
            $stockInDate = CarbonImmutable::now()->subDays(20 - min($index, 19))->setTime(8 + ($index % 9), 10);

            $stockInExtra = StockIn::query()->create([
                'business_id' => $business->id,
                'warehouse_id' => $warehouse->id,
                'supplier_id' => $supplier->id,
                'created_by' => $creator->id,
                'stock_in_no' => sprintf('SI-%04d', 4 + $index),
                'reference_no' => sprintf('PO-%04d', 4 + $index),
                'stock_in_type' => $index % 5 === 0 ? 'opening' : 'purchase',
                'stock_in_date' => $stockInDate,
                'status' => $status,
                'subtotal' => 0,
                'discount_amount' => $index % 4 === 0 ? 50000 : 0,
                'total_amount' => 0,
                'note' => sprintf('Phieu nhap mo rong #%02d de test danh sach', $index),
            ]);

            $stockInItems = [];
            $subtotal = 0;

            for ($itemIndex = 0; $itemIndex < 5; $itemIndex++) {
                $product = $productPool[(($index - 1) * 5 + $itemIndex) % count($productPool)];
                $quantity = 8 + (($index + $itemIndex) % 10) * 2;
                $unitCost = (float) $product->cost_price + (($index + $itemIndex) % 4) * 500;
                $subtotal += $quantity * $unitCost;
                $stockInItems[] = [$product, $quantity, $unitCost];
            }

            $stockInExtra->update([
                'subtotal' => $subtotal,
                'total_amount' => $subtotal - (float) $stockInExtra->discount_amount,
            ]);

            $this->createStockInItems($stockInExtra, $stockInItems);
            $stockIns[] = $stockInExtra;

            if ($status === 'confirmed' && $index % 2 === 0) {
                $paidAmount = $index % 4 === 0
                    ? (float) $stockInExtra->total_amount
                    : round((float) $stockInExtra->total_amount * 0.55, 2);

                $payments[] = Payment::query()->create([
                    'business_id' => $business->id,
                    'order_id' => null,
                    'stock_in_id' => $stockInExtra->id,
                    'customer_id' => null,
                    'supplier_id' => $supplier->id,
                    'created_by' => $creator->id,
                    'payment_no' => sprintf('PAY-OUT-%04d', 4 + $index),
                    'direction' => 'out',
                    'method' => $index % 3 === 0 ? 'cash' : 'bank_transfer',
                    'status' => $index % 4 === 0 ? 'paid' : 'pending',
                    'amount' => $paidAmount,
                    'payment_date' => $stockInDate->addHours(3),
                    'reference_no' => $stockInExtra->stock_in_no,
                    'note' => sprintf('Thanh toan tu dong cho phieu nhap mo rong #%02d', $index),
                ]);
            }
        }

        for ($index = 1; $index <= 18; $index++) {
            $warehouse = $warehousePool[($index + 1) % count($warehousePool)];
            $customer = $customerPool[$index % count($customerPool)];
            $creator = $userPool[($index + 2) % count($userPool)];
            $status = match ($index % 4) {
                1 => 'completed',
                2 => 'confirmed',
                3 => 'draft',
                default => 'cancelled',
            };
            $orderDateExtra = CarbonImmutable::now()->subDays(12 - min($index, 11))->setTime(9 + ($index % 8), 20);

            $orderExtra = Order::query()->create([
                'business_id' => $business->id,
                'warehouse_id' => $warehouse->id,
                'customer_id' => $customer->id,
                'created_by' => $creator->id,
                'order_no' => sprintf('ORD-%04d', 4 + $index),
                'order_date' => $orderDateExtra,
                'status' => $status,
                'payment_status' => 'unpaid',
                'subtotal' => 0,
                'discount_amount' => 0,
                'shipping_amount' => 15000 + (($index % 4) * 5000),
                'total_amount' => 0,
                'paid_amount' => 0,
                'note' => sprintf('Don hang mo rong #%02d de test API', $index),
            ]);

            $orderItems = [];
            $subtotal = 0;
            $discountTotal = 0;

            for ($itemIndex = 0; $itemIndex < 3; $itemIndex++) {
                $product = $productPool[(($index - 1) * 3 + $itemIndex + 11) % count($productPool)];
                $quantity = 1 + (($index + $itemIndex) % 4);
                $unitPrice = (float) $product->sale_price + (($itemIndex + $index) % 3) * 1000;
                $discountAmount = ($index + $itemIndex) % 5 === 0 ? 2000 : 0;
                $subtotal += $quantity * $unitPrice;
                $discountTotal += $discountAmount;
                $orderItems[] = [$product, $quantity, $unitPrice, $discountAmount];
            }

            $paidAmount = match ($status) {
                'completed' => $subtotal - $discountTotal + (float) $orderExtra->shipping_amount,
                'confirmed' => round(($subtotal - $discountTotal + (float) $orderExtra->shipping_amount) * 0.4, 2),
                default => 0,
            };

            $paymentStatus = match ($status) {
                'completed' => 'paid',
                'confirmed' => 'partial',
                default => 'unpaid',
            };

            $orderExtra->update([
                'subtotal' => $subtotal,
                'discount_amount' => $discountTotal,
                'total_amount' => $subtotal - $discountTotal + (float) $orderExtra->shipping_amount,
                'paid_amount' => $paidAmount,
                'payment_status' => $paymentStatus,
            ]);

            $this->createOrderItems($orderExtra, $orderItems);
            $orders[] = $orderExtra;

            if (in_array($status, ['completed', 'confirmed'], true)) {
                $stockOutStatus = $status === 'completed' || $index % 3 !== 0 ? 'confirmed' : 'draft';

                $stockOutExtra = StockOut::query()->create([
                    'business_id' => $business->id,
                    'warehouse_id' => $warehouse->id,
                    'order_id' => $orderExtra->id,
                    'customer_id' => $customer->id,
                    'created_by' => $creator->id,
                    'stock_out_no' => sprintf('SO-%04d', 3 + $index),
                    'reference_no' => $orderExtra->order_no,
                    'stock_out_type' => 'sale',
                    'stock_out_date' => $orderDateExtra->addHour(),
                    'status' => $stockOutStatus,
                    'subtotal' => $subtotal,
                    'total_amount' => $subtotal,
                    'note' => sprintf('Phieu xuat mo rong cho don #%02d', $index),
                ]);

                $stockOutItems = collect($orderItems)
                    ->map(fn (array $item) => [$item[0], $item[1], $item[2]])
                    ->all();

                $this->createStockOutItems($stockOutExtra, $stockOutItems);
                $stockOuts[] = $stockOutExtra;
            }

            if ($status === 'completed' || $status === 'confirmed') {
                $payments[] = Payment::query()->create([
                    'business_id' => $business->id,
                    'order_id' => $orderExtra->id,
                    'stock_in_id' => null,
                    'customer_id' => $customer->id,
                    'supplier_id' => null,
                    'created_by' => $creator->id,
                    'payment_no' => sprintf('PAY-IN-%04d', 3 + $index),
                    'direction' => 'in',
                    'method' => $index % 2 === 0 ? 'cash' : 'bank_transfer',
                    'status' => 'paid',
                    'amount' => $paidAmount,
                    'payment_date' => $orderDateExtra->addHours(2),
                    'reference_no' => $orderExtra->order_no,
                    'note' => sprintf('Thu tien cho don hang mo rong #%02d', $index),
                ]);
            } elseif ($status === 'draft' && $index % 3 === 0) {
                $payments[] = Payment::query()->create([
                    'business_id' => $business->id,
                    'order_id' => $orderExtra->id,
                    'stock_in_id' => null,
                    'customer_id' => $customer->id,
                    'supplier_id' => null,
                    'created_by' => $creator->id,
                    'payment_no' => sprintf('PAY-IN-%04d', 50 + $index),
                    'direction' => 'in',
                    'method' => 'e_wallet',
                    'status' => 'pending',
                    'amount' => round((float) $orderExtra->total_amount * 0.2, 2),
                    'payment_date' => $orderDateExtra->addMinutes(30),
                    'reference_no' => $orderExtra->order_no,
                    'note' => sprintf('Dat coc cho don nhap mo rong #%02d', $index),
                ]);
            }
        }

        for ($index = 1; $index <= 10; $index++) {
            $warehouse = $warehousePool[$index % count($warehousePool)];
            $creator = $userPool[($index + 3) % count($userPool)];
            $status = $index <= 7 ? 'confirmed' : 'draft';
            $adjustmentDateExtra = CarbonImmutable::now()->subDays(8 - min($index, 7))->setTime(17, 5 + $index);

            $adjustmentExtra = StockAdjustment::query()->create([
                'business_id' => $business->id,
                'warehouse_id' => $warehouse->id,
                'created_by' => $creator->id,
                'adjustment_no' => sprintf('ADJ-%04d', 3 + $index),
                'adjustment_date' => $adjustmentDateExtra,
                'reason' => sprintf('Kiem kho bo sung #%02d', $index),
                'status' => $status,
                'note' => sprintf('Phieu dieu chinh mo rong #%02d', $index),
            ]);

            $adjustmentItems = [];

            for ($itemIndex = 0; $itemIndex < 3; $itemIndex++) {
                $product = $productPool[(($index - 1) * 3 + $itemIndex + 25) % count($productPool)];
                $expectedQty = 10 + (($index + $itemIndex) % 8) * 3;
                $differenceQty = match (($index + $itemIndex) % 3) {
                    0 => -2,
                    1 => 1,
                    default => 3,
                };
                $countedQty = $expectedQty + $differenceQty;
                $unitCost = (float) $product->cost_price;
                $adjustmentItems[] = [$product, $expectedQty, $countedQty, $unitCost, sprintf('Dong kiem kho phu #%02d-%d', $index, $itemIndex + 1)];
            }

            $this->createStockAdjustmentItems($adjustmentExtra, $adjustmentItems);
            $adjustments[] = $adjustmentExtra;
        }

        return [
            'stock_in' => $stockIns,
            'orders' => $orders,
            'stock_out' => $stockOuts,
            'adjustments' => $adjustments,
            'payments' => $payments,
        ];
    }

    /**
     * Tạo item cho phiếu nhập kho.
     *
     * Item lưu snapshot tên, SKU và giá nhập tại thời điểm phát sinh.
     */
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

    /**
     * Tạo item cho đơn hàng.
     *
     * Snapshot này giúp đơn cũ không bị thay đổi khi catalog sản phẩm đổi tên hoặc đổi giá.
     */
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

    /**
     * Tạo item cho phiếu xuất kho.
     *
     * `unit_price` ở đây phản ánh giá bán trên chứng từ,
     * còn giá vốn thực tế sẽ được read model và ledger xử lý riêng.
     */
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

    /**
     * Tạo item cho chứng từ kiểm kho.
     *
     * `difference_qty` được tính trực tiếp từ `counted_qty - expected_qty`
     * để có thể dùng ngay cho ledger và báo cáo chênh lệch.
     */
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
     *
     * Dựng read model tồn kho từ bộ chứng từ demo.
     *
     * Seeder này không gọi service ledger,
     * mà dựng trực tiếp `inventory_movements` và `current_stocks`
     * để giữ cho dữ liệu mẫu rõ ràng, dễ đọc và dễ kiểm soát.
     */
    protected function seedInventoryReadModels(
        Business $business,
        array $users,
        array $warehouses,
        array $products,
        array $documents
    ): void {
        // Biến tạm này mô phỏng trạng thái tồn sau từng movement để cuối cùng ghi vào current_stocks.
        $stockBalances = [];

        foreach ($documents['stock_in'] as $stockIn) {
            if ($stockIn->status !== 'confirmed') {
                continue;
            }

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

        foreach ($documents['stock_out'] as $stockOut) {
            if ($stockOut->status !== 'confirmed') {
                continue;
            }

            foreach ($stockOut->items as $item) {
                InventoryMovement::query()->create([
                    'business_id' => $business->id,
                    'warehouse_id' => $stockOut->warehouse_id,
                    'product_id' => $item->product_id,
                    'movement_type' => 'stock_out',
                    'source_type' => 'stock_out',
                    'source_id' => $stockOut->id,
                    'source_code' => $stockOut->stock_out_no,
                    'quantity_change' => -1 * $item->quantity,
                    'unit_cost' => $item->product->cost_price,
                    'total_cost' => -1 * $item->quantity * $item->product->cost_price,
                    'movement_date' => $stockOut->stock_out_date,
                    'note' => $stockOut->note,
                    'created_by' => $stockOut->created_by,
                ]);

                $this->applyStockBalance(
                    $stockBalances,
                    $business->id,
                    $stockOut->warehouse_id,
                    $item->product_id,
                    -1 * (float) $item->quantity,
                    (float) $item->product->cost_price,
                    $stockOut->stock_out_date
                );
            }
        }

        foreach ($documents['adjustments'] as $adjustment) {
            if ($adjustment->status !== 'confirmed') {
                continue;
            }

            foreach ($adjustment->items as $item) {
                $movementType = $item->difference_qty >= 0 ? 'adjustment_in' : 'adjustment_out';

                InventoryMovement::query()->create([
                    'business_id' => $business->id,
                    'warehouse_id' => $adjustment->warehouse_id,
                    'product_id' => $item->product_id,
                    'movement_type' => $movementType,
                    'source_type' => 'stock_adjustment',
                    'source_id' => $adjustment->id,
                    'source_code' => $adjustment->adjustment_no,
                    'quantity_change' => $item->difference_qty,
                    'unit_cost' => $item->unit_cost,
                    'total_cost' => $item->line_total,
                    'movement_date' => $adjustment->adjustment_date,
                    'note' => $item->note,
                    'created_by' => $adjustment->created_by,
                ]);

                $this->applyStockBalance(
                    $stockBalances,
                    $business->id,
                    $adjustment->warehouse_id,
                    $item->product_id,
                    (float) $item->difference_qty,
                    (float) $item->unit_cost,
                    $adjustment->adjustment_date
                );
            }
        }

        foreach ($stockBalances as $balance) {
            CurrentStock::query()->create($balance);
        }
    }

    /**
     * Cộng dồn số lượng và giá trị tồn cho một cặp business - kho - sản phẩm.
     *
     * Hàm này đóng vai trò bản rút gọn của logic moving average
     * để seed ra `current_stocks` nhất quán với chuỗi `inventory_movements`.
     */
    protected function applyStockBalance(
        array &$stockBalances,
        int $businessId,
        int $warehouseId,
        int $productId,
        float $quantityDelta,
        float $unitCost,
        mixed $movementDate
    ): void {
        // Dùng key chuỗi để gom trạng thái tồn cho từng cặp kho - sản phẩm.
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

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function withNameSlug(array $attributes): array
    {
        if (isset($attributes['name'])) {
            $attributes['name_slug'] = NameSlug::from((string) $attributes['name']);
        }

        return $attributes;
    }
}
