<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\Concerns\InteractsWithBusinessApi;
use Tests\TestCase;

class PaymentSummaryFlowTest extends TestCase
{
    use InteractsWithBusinessApi;
    use RefreshDatabase;

    protected Business $business;

    protected User $owner;

    protected Warehouse $warehouse;

    protected Customer $customer;

    protected Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->disableJwtMiddleware();

        $this->business = Business::query()->create([
            'code' => 'payment-shop',
            'name' => 'Payment Shop',
            'email' => 'owner@payment-shop.local',
            'status' => 'active',
            'currency_code' => 'VND',
            'timezone' => 'Asia/Ho_Chi_Minh',
        ]);

        $this->owner = User::query()->create([
            'name' => 'Owner',
            'email' => 'owner@payment-shop.local',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $this->attachUserToBusiness($this->owner, $this->business->id);

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
            'name' => 'Khach payment',
            'phone' => '0909000001',
            'is_active' => true,
        ]);

        $this->actingAsBusinessUser($this->owner);
    }

    public function test_paid_payment_marks_an_order_as_partial_when_amount_is_not_enough(): void
    {
        $order = $this->createOrderWithTotal(30000);

        $response = $this->postJson('/api/payments', [
            'order_id' => $order->id,
            'customer_id' => $this->customer->id,
            'direction' => 'in',
            'method' => 'cash',
            'status' => 'paid',
            'amount' => 10000,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.order_id', $order->id)
            ->assertJsonPath('data.amount', '10000.00');

        $order->refresh();
        $this->assertSame('10000.00', $order->paid_amount);
        $this->assertSame('partial', $order->payment_status);
    }

    public function test_updating_a_payment_to_another_order_resyncs_both_orders(): void
    {
        $firstOrder = $this->createOrderWithTotal(30000);
        $secondOrder = $this->createOrderWithTotal(30000);

        $createPaymentResponse = $this->postJson('/api/payments', [
            'order_id' => $firstOrder->id,
            'customer_id' => $this->customer->id,
            'direction' => 'in',
            'method' => 'cash',
            'status' => 'paid',
            'amount' => 30000,
        ]);

        $createPaymentResponse->assertOk();
        $paymentId = $createPaymentResponse->json('data.id');

        $firstOrder->refresh();
        $this->assertSame('30000.00', $firstOrder->paid_amount);
        $this->assertSame('paid', $firstOrder->payment_status);

        $this->putJson("/api/payments/{$paymentId}", [
            'order_id' => $secondOrder->id,
            'customer_id' => $this->customer->id,
        ])->assertOk()
            ->assertJsonPath('data.order_id', $secondOrder->id);

        $firstOrder->refresh();
        $secondOrder->refresh();

        $this->assertSame('0.00', $firstOrder->paid_amount);
        $this->assertSame('unpaid', $firstOrder->payment_status);
        $this->assertSame('30000.00', $secondOrder->paid_amount);
        $this->assertSame('paid', $secondOrder->payment_status);
    }

    protected function createOrderWithTotal(float $unitPrice): Order
    {
        $product = Product::query()->create([
            'business_id' => $this->business->id,
            'unit_id' => $this->unit->id,
            'sku' => 'SKU-'.Str::upper(Str::random(6)),
            'name' => 'San pham payment',
            'product_type' => 'simple',
            'track_inventory' => true,
            'cost_price' => 10000,
            'sale_price' => $unitPrice,
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/orders', [
            'warehouse_id' => $this->warehouse->id,
            'customer_id' => $this->customer->id,
            'status' => 'confirmed',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => $unitPrice,
                    'discount_amount' => 0,
                ],
            ],
        ]);

        $response->assertOk();

        return Order::query()->findOrFail($response->json('data.id'));
    }
}
