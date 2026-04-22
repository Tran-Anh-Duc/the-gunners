<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\WarehouseDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseDocumentServicePayloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_prepare_document_payload_for_update_should_set_updated_by_not_created_by(): void
    {
        $user = User::query()->create([
            'name' => 'Payload Tester',
            'email' => 'payload-tester@test.local',
            'password' => 'password',
            'is_active' => true,
        ]);

        $this->actingAs($user);

        $payload = $this->invokeProtectedMethod('prepareDocumentPayloadAndRows', [
            [
                'document_type' => 'import',
                'warehouse_id' => 1,
                'document_date' => '2026-04-22',
                'status' => 'draft',
                'details' => [
                    [
                        'product_id' => 1,
                        'product_name' => 'Any',
                        'unit_id' => 1,
                        'unit_name' => 'Any',
                        'quantity' => 2,
                        'unit_price' => 100,
                        'tax_rate' => 10,
                    ],
                ],
            ],
            99,
            true,
        ])['payload'];

        $this->assertArrayHasKey('updated_by', $payload);
        $this->assertArrayNotHasKey('created_by', $payload);
    }

    public function test_calculate_detail_amounts_excluding_tax_returns_expected_values(): void
    {
        $amounts = $this->invokeProtectedMethod('calculateDetailAmounts', [2.0, 100.0, 10.0, false]);

        $this->assertSame(200.0, $amounts['subtotal']);
        $this->assertSame(20.0, $amounts['tax_price']);
        $this->assertSame(220.0, $amounts['total_price']);
    }

    public function test_calculate_detail_amounts_including_tax_returns_expected_values(): void
    {
        $amounts = $this->invokeProtectedMethod('calculateDetailAmounts', [2.0, 110.0, 10.0, true]);

        $this->assertSame(200.0, $amounts['subtotal']);
        $this->assertSame(20.0, $amounts['tax_price']);
        $this->assertSame(220.0, $amounts['total_price']);
    }

    public function test_prepare_detail_rows_and_totals_aggregates_values(): void
    {
        $prepared = $this->invokeProtectedMethod('prepareDetailRowsAndTotals', [[
            [
                'product_id' => 1,
                'product_name' => 'P1',
                'unit_id' => 1,
                'unit_name' => 'U1',
                'quantity' => 2,
                'unit_price' => 100,
                'tax_rate' => 10,
            ],
            [
                'product_id' => 2,
                'product_name' => 'P2',
                'unit_id' => 1,
                'unit_name' => 'U1',
                'quantity' => 1,
                'unit_price' => 50,
                'tax_rate' => 0,
            ],
        ], false]);

        $this->assertCount(2, $prepared['rows']);
        $this->assertSame(250.0, $prepared['totals']['subtotal_amount']);
        $this->assertSame(20.0, $prepared['totals']['tax_amount']);
        $this->assertSame(270.0, $prepared['totals']['total_amount']);
    }

    public function test_prepare_document_payload_and_rows_with_details_on_create_includes_totals_and_rows(): void
    {
        $user = User::query()->create([
            'name' => 'Create Tester',
            'email' => 'create-tester@test.local',
            'password' => 'password',
            'is_active' => true,
        ]);

        $this->actingAs($user);

        $prepared = $this->invokeProtectedMethod('prepareDocumentPayloadAndRows', [
            [
                'document_type' => 'import',
                'warehouse_id' => 1,
                'document_date' => '2026-04-22',
                'status' => 'confirmed',
                'details' => [
                    [
                        'product_id' => 1,
                        'product_name' => 'Any',
                        'unit_id' => 1,
                        'unit_name' => 'Any',
                        'quantity' => 2,
                        'unit_price' => 100,
                        'tax_rate' => 10,
                    ],
                ],
            ],
            99,
            false,
        ]);

        $this->assertCount(1, $prepared['rows']);
        $this->assertSame(200.0, $prepared['payload']['subtotal_amount']);
        $this->assertSame(20.0, $prepared['payload']['tax_amount']);
        $this->assertSame(220.0, $prepared['payload']['total_amount']);
        $this->assertArrayHasKey('created_by', $prepared['payload']);
        $this->assertArrayNotHasKey('updated_by', $prepared['payload']);
        $this->assertArrayHasKey('approved_at', $prepared['payload']);
    }

    public function test_prepare_document_payload_and_rows_without_details_on_update_keeps_rows_null(): void
    {
        $user = User::query()->create([
            'name' => 'Update Tester',
            'email' => 'update-tester@test.local',
            'password' => 'password',
            'is_active' => true,
        ]);

        $this->actingAs($user);

        $prepared = $this->invokeProtectedMethod('prepareDocumentPayloadAndRows', [
            [
                'note' => 'Header only update',
            ],
            99,
            true,
        ]);

        $this->assertNull($prepared['rows']);
        $this->assertArrayNotHasKey('subtotal_amount', $prepared['payload']);
        $this->assertArrayHasKey('updated_by', $prepared['payload']);
    }

    public function test_payload_for_save_update_clears_approval_when_status_is_cancelled(): void
    {
        $user = User::query()->create([
            'name' => 'Status Tester',
            'email' => 'status-tester@test.local',
            'password' => 'password',
            'is_active' => true,
        ]);

        $this->actingAs($user);

        $payload = $this->invokeProtectedMethod('payloadForSave', [[
            'status' => 'cancelled',
            'note' => 'Cancelled',
        ], 99, true]);

        $this->assertSame($user->id, $payload['updated_by']);
        $this->assertNull($payload['approved_by']);
        $this->assertNull($payload['approved_at']);
    }

    protected function invokeProtectedMethod(string $method, array $arguments): mixed
    {
        $reflection = new \ReflectionClass(WarehouseDocumentService::class);
        $service = $reflection->newInstanceWithoutConstructor();

        $targetMethod = $reflection->getMethod($method);
        $targetMethod->setAccessible(true);

        return $targetMethod->invokeArgs($service, $arguments);
    }
}
