<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\InteractsWithBusinessApi;
use Tests\TestCase;

class WarehouseDocumentModuleRegressionTest extends TestCase
{
    use InteractsWithBusinessApi;
    use RefreshDatabase;

    protected Business $business;

    protected User $owner;

    protected Unit $unit;

    protected Warehouse $warehouse;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->disableJwtMiddleware();

        $this->business = Business::query()->create([
            'code' => 'warehouse-doc-test',
            'name' => 'Warehouse Document Test',
            'email' => 'owner@warehouse-doc-test.local',
            'status' => 'active',
            'currency_code' => 'VND',
            'timezone' => 'Asia/Ho_Chi_Minh',
        ]);

        $this->owner = User::query()->create([
            'name' => 'Owner Warehouse Document',
            'email' => 'owner-warehouse-doc@test.local',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $this->attachUserToBusiness($this->owner, $this->business->id);
        $this->actingAsBusinessUser($this->owner);

        $this->unit = Unit::query()->create([
            'business_id' => $this->business->id,
            'name' => 'Cai',
            'is_active' => true,
        ]);

        $this->warehouse = Warehouse::query()->create([
            'business_id' => $this->business->id,
            'name' => 'Kho test',
            'is_active' => true,
        ]);

        $this->product = Product::query()->create([
            'business_id' => $this->business->id,
            'unit_id' => $this->unit->id,
            'sku' => 'SKU-WD-001',
            'name' => 'San pham test',
            'product_type' => 'simple',
            'track_inventory' => true,
            'cost_price' => 10000,
            'sale_price' => 15000,
            'is_active' => true,
        ]);
    }

    public function test_store_creates_document_with_details_and_totals(): void
    {
        $response = $this->postJson('/api/warehouse-documents', $this->baseStorePayload());

        $response->assertOk()
            ->assertJsonPath('data.business_id', $this->business->id)
            ->assertJsonPath('data.document_type', 'import')
            ->assertJsonPath('data.status', 'confirmed');

        $documentId = (int) $response->json('data.id');

        $this->assertDatabaseHas('warehouse_documents', [
            'id' => $documentId,
            'business_id' => $this->business->id,
            'warehouse_id' => $this->warehouse->id,
            'document_type' => 'import',
            'status' => 'confirmed',
            'subtotal_amount' => 200,
            'tax_amount' => 20,
            'total_amount' => 220,
            'approved_by' => $this->owner->id,
        ]);

        $this->assertDatabaseHas('warehouse_document_details', [
            'warehouse_document_id' => $documentId,
            'product_id' => $this->product->id,
            'unit_id' => $this->unit->id,
            'quantity' => 2,
            'subtotal' => 200,
            'tax_price' => 20,
            'total_price' => 220,
        ]);
    }

    public function test_store_generates_document_code_with_expected_prefix(): void
    {
        $response = $this->postJson('/api/warehouse-documents', $this->baseStorePayload());
        $response->assertOk();

        $documentCode = (string) $response->json('data.document_code');

        $this->assertNotSame('', $documentCode);
        $this->assertMatchesRegularExpression('/^WH[A-Z-]*-\d{4,}$/', $documentCode);
    }

    public function test_store_defaults_status_to_draft_when_missing(): void
    {
        $payload = $this->baseStorePayload();
        unset($payload['status']);

        $response = $this->postJson('/api/warehouse-documents', $payload);

        $response->assertOk()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.approved_at', null);

        $documentId = (int) $response->json('data.id');

        $this->assertDatabaseHas('warehouse_documents', [
            'id' => $documentId,
            'status' => 'draft',
            'approved_by' => null,
        ]);
    }

    public function test_store_rejects_invalid_document_type(): void
    {
        $response = $this->postJson('/api/warehouse-documents', $this->baseStorePayload([
            'document_type' => 'transfer',
        ]));

        $response->assertStatus(422)
            ->assertJsonPath('code', 'error_failed')
            ->assertJsonStructure(['data' => ['document_type']]);
    }

    public function test_store_rejects_missing_detail_quantity(): void
    {
        $payload = $this->baseStorePayload();
        unset($payload['details'][0]['quantity']);

        $response = $this->postJson('/api/warehouse-documents', $payload);

        $response->assertStatus(422)
            ->assertJsonPath('code', 'error_failed');

        $this->assertArrayHasKey('details.0.quantity', $response->json('data'));
    }

    public function test_index_only_lists_documents_in_current_business(): void
    {
        $currentDocumentId = $this->createWarehouseDocument();
        $foreignDocumentId = $this->createForeignBusinessDocument();

        $response = $this->getJson('/api/warehouse-documents');

        $response->assertOk()
            ->assertJsonPath('data.total', 1);

        $ids = collect($response->json('data.items'))->pluck('id')->all();

        $this->assertContains($currentDocumentId, $ids);
        $this->assertNotContains($foreignDocumentId, $ids);
    }

    public function test_index_filters_by_document_type_status_and_date_range(): void
    {
        $matchingId = $this->createWarehouseDocument([
            'document_type' => 'import',
            'status' => 'confirmed',
            'document_date' => '2026-04-20',
            'reference_code' => 'REF-MATCH',
        ]);

        $this->createWarehouseDocument([
            'document_type' => 'export',
            'status' => 'confirmed',
            'document_date' => '2026-04-20',
            'reference_code' => 'REF-EXPORT',
        ]);

        $this->createWarehouseDocument([
            'document_type' => 'import',
            'status' => 'draft',
            'document_date' => '2026-04-22',
            'reference_code' => 'REF-DRAFT',
        ]);

        $response = $this->getJson('/api/warehouse-documents?document_type=import&status=confirmed&document_date_from=2026-04-19&document_date_to=2026-04-21');

        $response->assertOk()
            ->assertJsonPath('data.total', 1);

        $items = $response->json('data.items');

        $this->assertCount(1, $items);
        $this->assertSame($matchingId, $items[0]['id']);
    }

    public function test_show_returns_404_for_document_outside_current_business(): void
    {
        $foreignDocumentId = $this->createForeignBusinessDocument();

        $this->getJson("/api/warehouse-documents/{$foreignDocumentId}")
            ->assertStatus(404);
    }

    public function test_show_returns_document_with_details_payload(): void
    {
        $documentId = $this->createWarehouseDocument();

        $response = $this->getJson("/api/warehouse-documents/{$documentId}");

        $response->assertOk()
            ->assertJsonPath('data.id', $documentId)
            ->assertJsonPath('data.document_type', 'import')
            ->assertJsonPath('data.details.0.product_id', $this->product->id)
            ->assertJsonPath('data.details.0.unit_id', $this->unit->id);
    }

    public function test_partial_update_should_keep_existing_fields_and_succeed(): void
    {
        $documentId = $this->createWarehouseDocument();

        $this->putJson("/api/warehouse-documents/{$documentId}", [
            'note' => 'Chi cap nhat ghi chu',
        ])->assertOk()
            ->assertJsonPath('data.document_type', 'import')
            ->assertJsonPath('data.status', 'confirmed')
            ->assertJsonPath('data.note', 'Chi cap nhat ghi chu');
    }

    public function test_update_with_details_replaces_rows_and_recalculates_totals(): void
    {
        $documentId = $this->createWarehouseDocument();

        $secondProduct = Product::query()->create([
            'business_id' => $this->business->id,
            'unit_id' => $this->unit->id,
            'sku' => 'SKU-WD-002',
            'name' => 'San pham thay the',
            'product_type' => 'simple',
            'track_inventory' => true,
            'cost_price' => 12000,
            'sale_price' => 18000,
            'is_active' => true,
        ]);

        $this->putJson("/api/warehouse-documents/{$documentId}", [
            'details' => [
                [
                    'product_id' => $secondProduct->id,
                    'product_name' => $secondProduct->name,
                    'unit_id' => $this->unit->id,
                    'unit_name' => $this->unit->name,
                    'quantity' => 3,
                    'unit_price' => 50,
                    'tax_rate' => 10,
                ],
            ],
        ])->assertOk();

        $this->assertDatabaseCount('warehouse_document_details', 1);

        $this->assertDatabaseHas('warehouse_document_details', [
            'warehouse_document_id' => $documentId,
            'product_id' => $secondProduct->id,
            'subtotal' => 150,
            'tax_price' => 15,
            'total_price' => 165,
        ]);

        $this->assertDatabaseMissing('warehouse_document_details', [
            'warehouse_document_id' => $documentId,
            'product_id' => $this->product->id,
        ]);

        $this->assertDatabaseHas('warehouse_documents', [
            'id' => $documentId,
            'subtotal_amount' => 150,
            'tax_amount' => 15,
            'total_amount' => 165,
        ]);
    }

    public function test_update_without_details_should_not_delete_existing_details(): void
    {
        $documentId = $this->createWarehouseDocument();

        $this->assertDatabaseCount('warehouse_document_details', 1);

        $this->putJson("/api/warehouse-documents/{$documentId}", [
            'document_type' => 'import',
            'warehouse_id' => $this->warehouse->id,
            'document_date' => '2026-04-22',
            'status' => 'confirmed',
            'note' => 'Cap nhat header',
        ])->assertOk();

        $this->assertDatabaseCount('warehouse_document_details', 1);
        $this->assertDatabaseHas('warehouse_documents', [
            'id' => $documentId,
            'subtotal_amount' => 200,
            'tax_amount' => 20,
            'total_amount' => 220,
        ]);
    }

    public function test_update_rejects_document_code_change(): void
    {
        $documentId = $this->createWarehouseDocument();

        $this->putJson("/api/warehouse-documents/{$documentId}", [
            'document_code' => 'WHIMPORT-9999',
        ])->assertStatus(422)
            ->assertJsonPath('code', 'error_failed')
            ->assertJsonStructure(['data' => ['document_code']]);
    }

    public function test_destroy_soft_deletes_document(): void
    {
        $documentId = $this->createWarehouseDocument();

        $this->deleteJson("/api/warehouse-documents/{$documentId}")
            ->assertOk();

        $this->assertSoftDeleted('warehouse_documents', [
            'id' => $documentId,
        ]);

        $this->getJson("/api/warehouse-documents/{$documentId}")
            ->assertStatus(404);
    }

    public function test_staff_role_cannot_access_warehouse_document_routes(): void
    {
        $documentId = $this->createWarehouseDocument();

        $staff = User::query()->create([
            'name' => 'Warehouse Staff',
            'email' => 'warehouse-staff@test.local',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $this->attachUserToBusiness($staff, $this->business->id, role: 'staff', isOwner: false);
        $this->actingAsBusinessUser($staff);

        $this->getJson('/api/warehouse-documents')
            ->assertStatus(403)
            ->assertJsonPath('error', 'Forbidden');

        $this->postJson('/api/warehouse-documents', $this->baseStorePayload())
            ->assertStatus(403);

        $this->putJson("/api/warehouse-documents/{$documentId}", ['note' => 'Forbidden'])
            ->assertStatus(403);

        $this->deleteJson("/api/warehouse-documents/{$documentId}")
            ->assertStatus(403);
    }

    protected function createWarehouseDocument(array $overrides = []): int
    {
        $response = $this->postJson('/api/warehouse-documents', $this->baseStorePayload($overrides));

        $response->assertOk();

        return (int) $response->json('data.id');
    }

    protected function baseStorePayload(array $overrides = []): array
    {
        return array_replace_recursive([
            'document_type' => 'import',
            'warehouse_id' => $this->warehouse->id,
            'document_date' => '2026-04-22',
            'status' => 'confirmed',
            'reference_code' => 'REF-TEST',
            'details' => [
                [
                    'product_id' => $this->product->id,
                    'product_name' => $this->product->name,
                    'unit_id' => $this->unit->id,
                    'unit_name' => $this->unit->name,
                    'quantity' => 2,
                    'unit_price' => 100,
                    'tax_rate' => 10,
                ],
            ],
        ], $overrides);
    }

    protected function createForeignBusinessDocument(): int
    {
        $foreignBusiness = Business::query()->create([
            'code' => 'warehouse-doc-foreign',
            'name' => 'Warehouse Document Foreign',
            'email' => 'owner@warehouse-doc-foreign.local',
            'status' => 'active',
            'currency_code' => 'VND',
            'timezone' => 'Asia/Ho_Chi_Minh',
        ]);

        $foreignWarehouse = Warehouse::query()->create([
            'business_id' => $foreignBusiness->id,
            'name' => 'Kho foreign',
            'is_active' => true,
        ]);

        $document = WarehouseDocument::query()->create([
            'business_id' => $foreignBusiness->id,
            'document_code' => 'WHIMPORT-9999',
            'document_type' => 'import',
            'warehouse_id' => $foreignWarehouse->id,
            'document_date' => '2026-04-22',
            'status' => 'draft',
            'subtotal_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 0,
            'created_by' => $this->owner->id,
        ]);

        return (int) $document->id;
    }
}
