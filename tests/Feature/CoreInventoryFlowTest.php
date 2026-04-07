<?php

namespace Tests\Feature;

use App\Http\Middleware\JwtMiddleware;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\CurrentStock;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class CoreInventoryFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Business $business;
    protected Unit $unit;
    protected Warehouse $warehouse;
    protected Customer $customer;
    protected Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        // Bộ test này bỏ qua JWT thật để tập trung xác nhận luồng nghiệp vụ và dữ liệu tồn kho.
        $this->withoutMiddleware(JwtMiddleware::class);

        $this->business = Business::query()->create([
            'code' => 'test-shop',
            'name' => 'Test Shop',
            'email' => 'owner@test-shop.local',
            'status' => 'active',
            'currency_code' => 'VND',
            'timezone' => 'Asia/Ho_Chi_Minh',
        ]);

        $this->user = User::query()->create([
            'name' => 'Owner',
            'email' => 'owner@test-shop.local',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        BusinessUser::query()->create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
            'status' => 'active',
            'is_owner' => true,
            'joined_at' => now(),
        ]);

        $this->unit = Unit::query()->create([
            'business_id' => $this->business->id,
            'name' => 'Cai',
            'is_active' => true,
        ]);

        $this->warehouse = Warehouse::query()->create([
            'business_id' => $this->business->id,
            'name' => 'Kho chinh',
            'is_active' => true,
        ]);

        $this->customer = Customer::query()->create([
            'business_id' => $this->business->id,
            'name' => 'Khach test',
            'phone' => '0909000999',
            'is_active' => true,
        ]);

        $this->supplier = Supplier::query()->create([
            'business_id' => $this->business->id,
            'name' => 'NCC test',
            'phone' => '02811112222',
            'is_active' => true,
        ]);

        $this->actingAsBusinessUser($this->user);
    }

    public function test_it_creates_a_product_in_the_current_business_scope(): void
    {
        // Mục tiêu: bảo đảm API tạo sản phẩm luôn tự gắn đúng business hiện tại.
        $response = $this->postJson('/api/products', [
            'sku' => 'SKU-TEST-001',
            'name' => 'Sản phẩm test',
            'unit_id' => $this->unit->id,
            'cost_price' => 10000,
            'sale_price' => 15000,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.business_id', $this->business->id)
            ->assertJsonPath('data.sku', 'SKU-TEST-001');

        $this->assertDatabaseHas('products', [
            'business_id' => $this->business->id,
            'sku' => 'SKU-TEST-001',
        ]);
    }

    public function test_it_auto_generates_stable_sku_when_frontend_does_not_send_one(): void
    {
        $firstResponse = $this->postJson('/api/products', [
            'name' => 'Sản phẩm tự sinh SKU 1',
            'unit_id' => $this->unit->id,
            'cost_price' => 10000,
            'sale_price' => 15000,
        ]);

        $secondResponse = $this->postJson('/api/products', [
            'name' => 'Sản phẩm tự sinh SKU 2',
            'unit_id' => $this->unit->id,
            'cost_price' => 12000,
            'sale_price' => 18000,
        ]);

        $firstResponse->assertOk();
        $secondResponse->assertOk();

        $firstSku = (string) $firstResponse->json('data.sku');
        $secondSku = (string) $secondResponse->json('data.sku');

        $this->assertMatchesRegularExpression('/^TEST-SHOP-[A-Z]{3}-000001$/', $firstSku);
        $this->assertMatchesRegularExpression('/^TEST-SHOP-[A-Z]{3}-000002$/', $secondSku);

        preg_match('/^TEST-SHOP-([A-Z]{3})-000001$/', $firstSku, $matches);

        $this->assertSame(sprintf('TEST-SHOP-%s-000002', $matches[1]), $secondSku);
        $this->assertDatabaseHas('business_sequences', [
            'business_id' => $this->business->id,
            'scope' => 'product.sku',
            'prefix' => $matches[1],
            'current_value' => 2,
        ]);
    }

    public function test_it_rejects_updating_sku_after_product_creation(): void
    {
        $product = $this->createProduct();

        $this->putJson("/api/products/{$product->id}", [
            'sku' => 'SKU-MOI-001',
            'name' => 'Tên mới',
        ])->assertStatus(422)
            ->assertJsonPath('code', 'error_failed')
            ->assertJsonStructure(['data' => ['sku']]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'sku' => $product->sku,
        ]);
    }

    public function test_stock_in_creates_inventory_movements_and_current_stock(): void
    {
        // Mục tiêu: khi phiếu nhập được confirm thì ledger và current stock phải cùng được cập nhật.
        $product = $this->createProduct();

        $response = $this->postJson('/api/stock-in', [
            'warehouse_id' => $this->warehouse->id,
            'supplier_id' => $this->supplier->id,
            'status' => 'confirmed',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 5,
                    'unit_cost' => 12000,
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'confirmed');

        $this->assertDatabaseHas('inventory_movements', [
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'movement_type' => 'stock_in',
            'quantity_change' => 5,
        ]);

        $this->assertDatabaseHas('current_stocks', [
            'business_id' => $this->business->id,
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $product->id,
            'quantity_on_hand' => 5,
        ]);
    }

    public function test_stock_adjustment_updates_current_stock_from_counted_quantity(): void
    {
        // Mục tiêu: số lượng kiểm thực tế (`counted_qty`) phải trở thành số tồn cuối cùng sau điều chỉnh.
        $product = $this->createProduct();
        $this->seedConfirmedStockIn($product, 10, 10000);

        $response = $this->postJson('/api/stock-adjustments', [
            'warehouse_id' => $this->warehouse->id,
            'status' => 'confirmed',
            'reason' => 'Kiểm kho',
            'items' => [
                [
                    'product_id' => $product->id,
                    'counted_qty' => 8,
                    'note' => 'That thoat 2 sản phẩm',
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.items.0.difference_qty', '-2.000');

        $stock = CurrentStock::query()
            ->where('business_id', $this->business->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->where('product_id', $product->id)
            ->first();

        $this->assertNotNull($stock);
        $this->assertSame('8.000', $stock->quantity_on_hand);
    }

    public function test_confirming_and_cancelling_a_draft_stock_in_updates_inventory(): void
    {
        // Mục tiêu: draft chưa ảnh hưởng tồn; confirm thì sinh movement; cancel thì gỡ movement ra khỏi ledger.
        $product = $this->createProduct();

        $createResponse = $this->postJson('/api/stock-in', [
            'warehouse_id' => $this->warehouse->id,
            'supplier_id' => $this->supplier->id,
            'status' => 'draft',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 4,
                    'unit_cost' => 11000,
                ],
            ],
        ]);

        $createResponse->assertOk();
        $stockInId = $createResponse->json('data.id');

        $this->assertDatabaseMissing('inventory_movements', [
            'source_type' => 'stock_in',
            'source_id' => $stockInId,
        ]);

        $confirmResponse = $this->postJson("/api/stock-in/{$stockInId}/confirm");
        $confirmResponse->assertOk()
            ->assertJsonPath('data.status', 'confirmed');

        $this->assertDatabaseHas('inventory_movements', [
            'source_type' => 'stock_in',
            'source_id' => $stockInId,
            'quantity_change' => 4,
        ]);

        $cancelResponse = $this->postJson("/api/stock-in/{$stockInId}/cancel");
        $cancelResponse->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseMissing('inventory_movements', [
            'source_type' => 'stock_in',
            'source_id' => $stockInId,
        ]);
    }

    public function test_confirming_stock_out_uses_moving_average_cost_and_updates_current_stock(): void
    {
        // Mục tiêu: xuất kho phải lấy giá vốn bình quân hiện tại thay vì tin cố định vào `cost_price`.
        $product = $this->createProduct();
        $this->seedConfirmedStockIn($product, 10, 10000);

        $secondStockIn = $this->postJson('/api/stock-in', [
            'warehouse_id' => $this->warehouse->id,
            'supplier_id' => $this->supplier->id,
            'status' => 'confirmed',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 10,
                    'unit_cost' => 20000,
                ],
            ],
        ]);
        $secondStockIn->assertOk();

        $stockOutResponse = $this->postJson('/api/stock-out', [
            'warehouse_id' => $this->warehouse->id,
            'customer_id' => $this->customer->id,
            'status' => 'draft',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 4,
                    'unit_price' => 15000,
                ],
            ],
        ]);

        $stockOutResponse->assertOk();
        $stockOutId = $stockOutResponse->json('data.id');

        $confirmResponse = $this->postJson("/api/stock-out/{$stockOutId}/confirm");
        $confirmResponse->assertOk()
            ->assertJsonPath('data.status', 'confirmed');

        $this->assertDatabaseHas('inventory_movements', [
            'source_type' => 'stock_out',
            'source_id' => $stockOutId,
            'unit_cost' => 15000,
            'total_cost' => -60000,
        ]);

        $stock = CurrentStock::query()
            ->where('business_id', $this->business->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->where('product_id', $product->id)
            ->first();

        $this->assertNotNull($stock);
        $this->assertSame('16.000', $stock->quantity_on_hand);
        $this->assertSame('15000.00', $stock->avg_unit_cost);
    }

    public function test_payment_updates_order_paid_amount_and_status(): void
    {
        $product = $this->createProduct();

        $orderResponse = $this->postJson('/api/orders', [
            'warehouse_id' => $this->warehouse->id,
            'customer_id' => $this->customer->id,
            'status' => 'confirmed',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => 15000,
                    'discount_amount' => 0,
                ],
            ],
        ]);

        $orderResponse->assertOk();
        $orderId = $orderResponse->json('data.id');

        $paymentResponse = $this->postJson('/api/payments', [
            'order_id' => $orderId,
            'customer_id' => $this->customer->id,
            'direction' => 'in',
            'method' => 'cash',
            'status' => 'paid',
            'amount' => 30000,
        ]);

        $paymentResponse->assertOk()
            ->assertJsonPath('data.order_id', $orderId);

        /** @var Order $order */
        $order = Order::query()->findOrFail($orderId);
        $this->assertSame('30000.00', $order->paid_amount);
        $this->assertSame('paid', $order->payment_status);
        $this->assertDatabaseHas('payments', [
            'id' => $paymentResponse->json('data.id'),
            'order_id' => $orderId,
        ]);
    }

    public function test_confirming_and_cancelling_payment_updates_order_payment_summary(): void
    {
        $product = $this->createProduct();

        $orderResponse = $this->postJson('/api/orders', [
            'warehouse_id' => $this->warehouse->id,
            'customer_id' => $this->customer->id,
            'status' => 'confirmed',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => 15000,
                    'discount_amount' => 0,
                ],
            ],
        ]);

        $orderResponse->assertOk();
        $orderId = $orderResponse->json('data.id');

        $paymentResponse = $this->postJson('/api/payments', [
            'order_id' => $orderId,
            'customer_id' => $this->customer->id,
            'direction' => 'in',
            'method' => 'cash',
            'status' => 'pending',
            'amount' => 30000,
        ]);

        $paymentResponse->assertOk();
        $paymentId = $paymentResponse->json('data.id');

        $order = Order::query()->findOrFail($orderId);
        $this->assertSame('0.00', $order->paid_amount);
        $this->assertSame('unpaid', $order->payment_status);

        $confirmResponse = $this->postJson("/api/payments/{$paymentId}/confirm");
        $confirmResponse->assertOk()
            ->assertJsonPath('data.status', 'paid');

        $order->refresh();
        $this->assertSame('30000.00', $order->paid_amount);
        $this->assertSame('paid', $order->payment_status);

        $cancelResponse = $this->postJson("/api/payments/{$paymentId}/cancel");
        $cancelResponse->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $order->refresh();
        $this->assertSame('0.00', $order->paid_amount);
        $this->assertSame('unpaid', $order->payment_status);
    }

    protected function actingAsBusinessUser(User $user): void
    {
        app()->instance('jwt_user', $user->fresh());
        auth()->setUser($user);
        $this->actingAs($user);
    }

    protected function createProduct(): Product
    {
        return Product::query()->create([
            'business_id' => $this->business->id,
            'unit_id' => $this->unit->id,
            'sku' => 'SKU-'.Str::upper(Str::random(6)),
            'name' => 'Sản phẩm test',
            'product_type' => 'simple',
            'track_inventory' => true,
            'cost_price' => 10000,
            'sale_price' => 15000,
            'status' => 'active',
        ]);
    }

    protected function seedConfirmedStockIn(Product $product, float $quantity, float $unitCost): void
    {
        $response = $this->postJson('/api/stock-in', [
            'warehouse_id' => $this->warehouse->id,
            'supplier_id' => $this->supplier->id,
            'status' => 'confirmed',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                ],
            ],
        ]);

        $response->assertOk();
    }
}
