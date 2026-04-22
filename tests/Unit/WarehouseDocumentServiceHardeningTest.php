<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\WarehouseDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseDocumentServiceHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_attach_document_id_to_rows_assigns_id_for_all_rows(): void
    {
        $rows = [
            ['product_id' => 1, 'subtotal' => 100],
            ['product_id' => 2, 'subtotal' => 200],
        ];

        $result = $this->invokeProtectedMethod('attachDocumentIdToRows', [$rows, 77]);

        $this->assertSame(77, $result[0]['warehouse_document_id']);
        $this->assertSame(77, $result[1]['warehouse_document_id']);
    }

    public function test_payload_for_save_create_confirmed_sets_creator_and_approval(): void
    {
        $user = User::query()->create([
            'name' => 'Create Confirmed Tester',
            'email' => 'create-confirmed-tester@test.local',
            'password' => 'password',
            'is_active' => true,
        ]);

        $this->actingAs($user);

        $payload = $this->invokeProtectedMethod('payloadForSave', [[
            'document_type' => 'import',
            'warehouse_id' => 1,
            'document_date' => '2026-04-22',
            'status' => 'confirmed',
            'subtotal_amount' => 100,
            'tax_amount' => 10,
            'total_amount' => 110,
        ], 99, false]);

        $this->assertSame($user->id, $payload['created_by']);
        $this->assertSame($user->id, $payload['approved_by']);
        $this->assertNotNull($payload['approved_at']);
    }

    public function test_payload_for_save_create_draft_has_no_approval_data(): void
    {
        $user = User::query()->create([
            'name' => 'Create Draft Tester',
            'email' => 'create-draft-tester@test.local',
            'password' => 'password',
            'is_active' => true,
        ]);

        $this->actingAs($user);

        $payload = $this->invokeProtectedMethod('payloadForSave', [[
            'document_type' => 'import',
            'warehouse_id' => 1,
            'document_date' => '2026-04-22',
            'status' => 'draft',
            'subtotal_amount' => 100,
            'tax_amount' => 10,
            'total_amount' => 110,
        ], 99, false]);

        $this->assertSame($user->id, $payload['created_by']);
        $this->assertNull($payload['approved_by']);
        $this->assertNull($payload['approved_at']);
    }

    public function test_prepare_document_payload_and_rows_with_price_includes_tax_calculates_totals(): void
    {
        $user = User::query()->create([
            'name' => 'Includes Tax Tester',
            'email' => 'includes-tax-tester@test.local',
            'password' => 'password',
            'is_active' => true,
        ]);

        $this->actingAs($user);

        $prepared = $this->invokeProtectedMethod('prepareDocumentPayloadAndRows', [[
            'document_type' => 'import',
            'warehouse_id' => 1,
            'document_date' => '2026-04-22',
            'status' => 'draft',
            'is_price_includes_tax' => true,
            'details' => [
                [
                    'product_id' => 1,
                    'product_name' => 'Any',
                    'unit_id' => 1,
                    'unit_name' => 'Any',
                    'quantity' => 2,
                    'unit_price' => 110,
                    'tax_rate' => 10,
                ],
            ],
        ], 99, false]);

        $this->assertSame(200.0, $prepared['payload']['subtotal_amount']);
        $this->assertSame(20.0, $prepared['payload']['tax_amount']);
        $this->assertSame(220.0, $prepared['payload']['total_amount']);

        $this->assertCount(1, $prepared['rows']);
        $this->assertSame(200.0, $prepared['rows'][0]['subtotal']);
        $this->assertSame(20.0, $prepared['rows'][0]['tax_price']);
        $this->assertSame(220.0, $prepared['rows'][0]['total_price']);
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
